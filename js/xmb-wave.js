/*
 * WCC XMB Wave Background
 * Adapted from fchavonet/creative_coding-xmb_wave_background (WebGL/GLSL).
 * 3 thick, soft, slow "silk string" waves behind all content, in the app accent
 * trio (sky / violet / teal), theme-aware for both dark and light.
 *
 * Per-user preference: localStorage key 'wccWaveBg' ('on' default | 'off').
 * Toggle live via window.wccSetWaveBg(true/false) (My Profile → Visual Preferences)
 * so users on weak machines can turn it off. No canvas is created when off.
 *
 * Perf: 60% internal render resolution, ~24fps cap, pauses when the tab is hidden,
 * honours prefers-reduced-motion, and no-ops if WebGL is unavailable.
 */
(function () {
    if (window.__wccWaveModule) return;
    window.__wccWaveModule = true;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var RES_SCALE = 0.6;
    var frameInterval = 1000 / 24;
    var state = null; // active wave instance, or null when off

    /* ---------------------------------------------------------------------
     * Continuous clock across page navigations.
     *
     * The shader is driven by uTime, and rAF hands us milliseconds since THIS
     * document loaded. In a multi-page app every menu click is a fresh document,
     * so that counter restarts at ~0 and the ribbon visibly snaps back to its
     * opening shape — the animation looked like it "refreshed" on every click.
     *
     * Fix: carry the elapsed time in sessionStorage and add it as an offset, so
     * uTime keeps counting up across navigations. sessionStorage (not local) is
     * deliberate: it is per-tab, so two open tabs each keep their own unbroken
     * ribbon instead of fighting over one shared clock.
     * ------------------------------------------------------------------- */
    var CLOCK_KEY = 'wccWaveClock';
    var clockBase = 0;   // ms of wave-time accumulated before this document
    var lastSaved = 0;

    // 24h ceiling: the waves are slow (0.024-0.09 rad/s) so highp floats hold accuracy
    // far beyond this, but a corrupted or absurd stored value must not poison the phase.
    var CLOCK_MAX = 24 * 60 * 60 * 1000;

    try {
        var stored = parseFloat(sessionStorage.getItem(CLOCK_KEY));
        if (isFinite(stored) && stored > 0) clockBase = stored % CLOCK_MAX;
    } catch (e) { /* private mode / storage blocked — start from 0 */ }

    function waveTime(now) { return clockBase + now; }

    function saveClock(now) {
        try { sessionStorage.setItem(CLOCK_KEY, String(waveTime(now))); } catch (e) {}
    }

    // pagehide is the reliable "leaving now" signal (it fires on bfcache too, where
    // unload does not). The periodic write in the render loop is belt-and-braces for
    // browsers that skip it on hard navigation or a crash.
    window.addEventListener('pagehide', function () { saveClock(performance.now()); });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) saveClock(performance.now());
    });

    function compile(gl, src, type) {
        var sh = gl.createShader(type);
        gl.shaderSource(sh, src);
        gl.compileShader(sh);
        if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) {
            console.warn('[WCC wave] shader:', gl.getShaderInfoLog(sh));
            return null;
        }
        return sh;
    }

    function build() {
        if (document.getElementById('wccWaveBg')) return null;
        var canvas = document.createElement('canvas');
        canvas.id = 'wccWaveBg';
        canvas.setAttribute('aria-hidden', 'true');
        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return null;
        document.body.prepend(canvas);

        var vsSource = 'attribute vec2 p; void main(){ gl_Position = vec4(p, 0.0, 1.0); }';
        var fsSource = [
            'precision highp float;',
            'uniform float uTime;',
            'uniform vec2  uResolution;',
            'uniform vec3  uColorA;',
            'uniform vec3  uColorB;',
            'uniform vec3  uColorC;',
            'uniform float uAlpha;',
            'const float W = 1.5;',
            'float calcSine(vec2 uv, float speed, float freq, float amp, float phase, float off, float lw, float sharp){',
            '  float ang = uTime*speed*freq*-1.0 + (phase+uv.x)*2.0;',
            '  float wy = sin(ang)*amp + off;',
            '  float dy = wy - uv.y;',
            '  float d  = distance(wy, uv.y);',
            '  if (dy < 0.0) d = d*4.0;',
            '  float s = smoothstep(lw*W, 0.0, d);',
            '  return pow(s, sharp);',
            '}',
            'void main(){',
            '  vec2 uv = gl_FragCoord.xy / uResolution;',
            '  float a = calcSine(uv, 0.12, 0.20, 0.22, 0.0, 0.50, 0.16,  7.0);',
            '  float b = calcSine(uv, 0.18, 0.40, 0.16, 0.0, 0.50, 0.14,  8.0);',
            '  float c = calcSine(uv, 0.15, 0.60, 0.14, 0.0, 0.50, 0.12, 10.0);',
            '  vec3 col = uColorA*a + uColorB*b + uColorC*c;',
            '  float alpha = clamp(a + b + c, 0.0, 1.0) * uAlpha;',
            '  if (alpha <= 0.001) discard;',
            '  gl_FragColor = vec4(col, alpha);',
            '}'
        ].join('\n');

        var vs = compile(gl, vsSource, gl.VERTEX_SHADER);
        var fs = compile(gl, fsSource, gl.FRAGMENT_SHADER);
        if (!vs || !fs) { canvas.remove(); return null; }

        var prog = gl.createProgram();
        gl.attachShader(prog, vs);
        gl.attachShader(prog, fs);
        gl.linkProgram(prog);
        if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) { canvas.remove(); return null; }
        gl.useProgram(prog);

        var buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        var pLoc = gl.getAttribLocation(prog, 'p');
        gl.enableVertexAttribArray(pLoc);
        gl.vertexAttribPointer(pLoc, 2, gl.FLOAT, false, 0, 0);

        var s = {
            canvas: canvas, gl: gl,
            uTime: gl.getUniformLocation(prog, 'uTime'),
            uRes: gl.getUniformLocation(prog, 'uResolution'),
            uA: gl.getUniformLocation(prog, 'uColorA'),
            uB: gl.getUniformLocation(prog, 'uColorB'),
            uC: gl.getUniformLocation(prog, 'uColorC'),
            uAlpha: gl.getUniformLocation(prog, 'uAlpha'),
            running: false, rafId: 0, last: 0
        };

        gl.enable(gl.BLEND);
        gl.blendFunc(gl.SRC_ALPHA, gl.ONE_MINUS_SRC_ALPHA);
        gl.clearColor(0, 0, 0, 0);

        var DARK = { a: [0.22, 0.74, 0.97], b: [0.65, 0.55, 0.98], c: [0.18, 0.83, 0.75] };
        var LIGHT = { a: [0.02, 0.52, 0.78], b: [0.49, 0.23, 0.93], c: [0.05, 0.58, 0.53] };
        s.applyTheme = function () {
            var light = document.documentElement.classList.contains('light-theme');
            var t = light ? LIGHT : DARK;
            gl.uniform3fv(s.uA, t.a);
            gl.uniform3fv(s.uB, t.b);
            gl.uniform3fv(s.uC, t.c);
            gl.uniform1f(s.uAlpha, light ? 0.85 : 0.9);
        };
        s.resize = function () {
            var w = Math.max(1, Math.round(window.innerWidth * RES_SCALE));
            var h = Math.max(1, Math.round(window.innerHeight * RES_SCALE));
            canvas.width = w; canvas.height = h;
            gl.viewport(0, 0, w, h);
            gl.uniform2f(s.uRes, w, h);
        };
        return s;
    }

    /** Draw exactly one frame at the given wave-time. */
    function draw(now) {
        state.gl.clear(state.gl.COLOR_BUFFER_BIT);
        // Absolute, monotonic, and carried across navigations — so the waves never
        // snap on a loop boundary, pause/resume can't jump phase, and a menu click
        // resumes the ribbon exactly where the previous page left it.
        state.gl.uniform1f(state.uTime, waveTime(now) * 0.001);
        state.gl.drawArrays(state.gl.TRIANGLE_STRIP, 0, 4);
    }

    function loop(now) {
        if (!state || !state.running) return;
        if (now - state.last >= frameInterval) {
            state.last = now;
            draw(now);
            // Checkpoint roughly once a second so an abrupt close still resumes close
            // to where it stopped. Cheap next to a WebGL draw.
            if (now - lastSaved >= 1000) { lastSaved = now; saveClock(now); }
        }
        state.rafId = requestAnimationFrame(loop);
    }

    function startWave() {
        if (state) return;
        state = build();
        if (!state) return; // WebGL unavailable — gradient remains
        state.applyTheme();
        state.resize();
        if (reduceMotion) {
            state.gl.clear(state.gl.COLOR_BUFFER_BIT);
            state.gl.uniform1f(state.uTime, 8.0);
            state.gl.drawArrays(state.gl.TRIANGLE_STRIP, 0, 4);
        } else {
            state.running = true;
            // Paint the resumed frame NOW rather than waiting for the first rAF
            // callback. Without this there is a visible blank behind the page on
            // every navigation while WebGL warms up, which reads as a flicker even
            // though the clock itself is continuous.
            draw(performance.now());
            state.rafId = requestAnimationFrame(loop);
        }
    }

    function stopWave() {
        if (!state) return;
        state.running = false;
        if (state.rafId) cancelAnimationFrame(state.rafId);
        if (state.canvas) state.canvas.remove();
        state = null;
    }

    // Shared listeners (attached once; they no-op while the wave is off).
    window.addEventListener('resize', function () { if (state) state.resize(); });
    window.addEventListener('wcc:themechange', function () { if (state) state.applyTheme(); });
    document.addEventListener('visibilitychange', function () {
        if (!state || reduceMotion) return;
        if (document.hidden) {
            state.running = false;
        } else if (!state.running) {
            state.running = true;
            state.last = 0;
            state.rafId = requestAnimationFrame(loop);
        }
    });

    // Public toggle — persists the per-user preference and applies it live.
    window.wccSetWaveBg = function (on) {
        try { localStorage.setItem('wccWaveBg', on ? 'on' : 'off'); } catch (e) {}
        if (on) startWave(); else stopWave();
    };
    window.wccWaveBgEnabled = function () {
        try { return localStorage.getItem('wccWaveBg') !== 'off'; } catch (e) { return true; }
    };

    function boot() {
        if (window.wccWaveBgEnabled()) startWave();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
