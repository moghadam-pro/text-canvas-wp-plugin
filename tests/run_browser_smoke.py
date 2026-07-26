#!/usr/bin/env python3
import base64
import json
import pathlib
import subprocess
import sys
import tempfile
import time

import requests
import websocket

ROOT = pathlib.Path(__file__).resolve().parents[1]
OUT = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else ROOT / 'tests' / 'artifacts')
OUT.mkdir(parents=True, exist_ok=True)
DEBUG_PORT = 9333
PROFILE = tempfile.mkdtemp(prefix='mpro-chrome-')

fixture = (ROOT / 'tests' / 'browser-fixture.html').read_text(encoding='utf-8')
css = (ROOT / 'assets' / 'css' / 'frontend.css').read_text(encoding='utf-8')
js = (ROOT / 'assets' / 'js' / 'frontend.js').read_text(encoding='utf-8')
fixture = fixture.replace('<link rel="stylesheet" href="../assets/css/frontend.css">', '<style>' + css + '</style>')
fixture = fixture.replace('<script src="../assets/js/frontend.js"></script>', '<script>' + js.replace('</script>', '<\\/script>') + '</script>')

chrome = subprocess.Popen(
    [
        'xvfb-run', '-a', 'chromium', '--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu',
        '--remote-allow-origins=*', f'--remote-debugging-port={DEBUG_PORT}', f'--user-data-dir={PROFILE}',
        'about:blank',
    ],
    stdout=subprocess.DEVNULL,
    stderr=subprocess.DEVNULL,
)

try:
    for _ in range(100):
        try:
            requests.get(f'http://127.0.0.1:{DEBUG_PORT}/json/version', timeout=.2).raise_for_status()
            break
        except Exception:
            time.sleep(.1)
    else:
        raise RuntimeError('Chromium debugging port did not open')

    target = requests.put(f'http://127.0.0.1:{DEBUG_PORT}/json/new?about:blank', timeout=2).json()
    ws = websocket.create_connection(target['webSocketDebuggerUrl'], timeout=10)
    counter = 0

    def call(method, params=None):
        nonlocal_counter = None
        global counter
        counter += 1
        current = counter
        ws.send(json.dumps({'id': current, 'method': method, 'params': params or {}}))
        while True:
            message = json.loads(ws.recv())
            if message.get('id') == current:
                if 'error' in message:
                    raise RuntimeError(message['error'])
                return message.get('result', {})

    call('Page.enable')
    call('Runtime.enable')
    frame_id = call('Page.getFrameTree')['frameTree']['frame']['id']

    for name, width, height in [('desktop', 1280, 874), ('mobile', 402, 874)]:
        call('Emulation.setDeviceMetricsOverride', {
            'width': width, 'height': height, 'deviceScaleFactor': 1, 'mobile': False,
        })
        call('Page.setDocumentContent', {'frameId': frame_id, 'html': fixture})
        deadline = time.time() + 8
        result = None
        while time.time() < deadline:
            evaluated = call('Runtime.evaluate', {
                'expression': '({tests:document.body&&document.body.dataset.tests,text:document.getElementById("test-results")&&document.getElementById("test-results").textContent,frame:(()=>{const el=document.querySelector(".mpro-tc__frame");if(!el)return null;const r=el.getBoundingClientRect();return [Math.round(r.width),Math.round(r.height)]})()})',
                'returnByValue': True,
            })
            result = evaluated.get('result', {}).get('value')
            if result and result.get('tests'):
                break
            time.sleep(.1)
        if not result or result.get('tests') != 'pass':
            raise RuntimeError(f'{name} smoke failed: {result}')
        expected = [744, 500] if name == 'desktop' else [370, 610]
        if result.get('frame') != expected:
            raise RuntimeError(f'{name} frame mismatch: {result.get("frame")} != {expected}')
        call('Runtime.evaluate', {'expression': 'document.getElementById("test-results").style.display="none"'})
        screenshot = call('Page.captureScreenshot', {'format': 'png', 'captureBeyondViewport': False})
        (OUT / f'{name}.png').write_bytes(base64.b64decode(screenshot['data']))
        (OUT / f'{name}.txt').write_text(result.get('text', ''), encoding='utf-8')

    ws.close()
    print(f'Browser smoke test passed. Artifacts: {OUT}')
finally:
    chrome.terminate()
    try:
        chrome.wait(timeout=5)
    except subprocess.TimeoutExpired:
        chrome.kill()
