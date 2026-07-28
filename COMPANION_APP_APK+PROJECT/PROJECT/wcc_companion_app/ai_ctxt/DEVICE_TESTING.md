# Device Testing — Build, Deploy & Drive the App from the Agent Shell

**Sole author:** Project owner  
**Package (Open Beta 1.0.0):** `com.wcc.companion`  
**Activity:** `com.wcc.companion/com.example.wcc_companion_app.MainActivity`

How to compile the companion app, push it to the physical phone, drive its UI, and read
back what happened from the agent shell.

Everything here is verified on **this** machine + device. Values in `CAPS` are the only
things you'd change for a different setup.

---

## 0. Constants (set these once per shell)

```bash
export JAVA_HOME="C:\Program Files\Android\Android Studio\jbr"     # gradle finds no Java otherwise
export ADB="$LOCALAPPDATA/Android/Sdk/platform-tools/adb.exe"
export DEV="adb-RZGL11F8L1E-filZx2._adb-tls-connect._tcp"          # this phone, over wireless adb
export PKG="com.wcc.companion"
export MAIN="$PKG/com.example.wcc_companion_app.MainActivity"
export MSYS_NO_PATHCONV=1        # STOP Git-Bash rewriting /sdcard/... into a Windows path
PROJ="<project_root>"
```

Sanity check the phone is reachable (wireless adb drops when the phone sleeps/leaves wifi):

```bash
"$ADB" devices          # want: <DEV>   device   (not "offline" / not absent)
```

---

## 1. Build the APK

```bash
cd "$PROJ" && ./gradlew.bat :app:assembleDebug --console=plain
# output: app/build/outputs/apk/debug/app-debug.apk
```

⚠️ **"BUILD SUCCESSFUL" is not proof it compiled.** This machine intermittently prints
`e: Could not connect to Kotlin compile daemon` and *still* ends BUILD SUCCESSFUL — that
run may have skipped compilation. Confirm real compilation one of two ways:

```bash
# a) re-run; a genuine build shows the task ran or is cached
./gradlew.bat :app:assembleDebug --console=plain 2>&1 | grep -iE "compileDebugKotlin|BUILD"
#    -> "> Task :app:compileDebugKotlin" (ran) or "UP-TO-DATE" (already built) = good
# b) or just check the APK is newer than your edit
ls -la app/build/outputs/apk/debug/app-debug.apk
```
Only treat a build as real when you see `compileDebugKotlin` (ran/UP-TO-DATE) with no `e:`
lines about the Kotlin source itself.

---

## 2. Install

```bash
"$ADB" -s "$DEV" install -r "$PROJ/app/build/outputs/apk/debug/app-debug.apk"   # want: Success
```
`adb: device ... not found` = the phone dropped off wifi adb; ask the user to reconnect.
`-r` reinstalls over the existing app, keeping its data (stays logged in).

---

## 3. Launch / restart / stop

```bash
"$ADB" -s "$DEV" shell am force-stop "$PKG"                 # kill it (start each test clean)
"$ADB" -s "$DEV" shell am start -n "$MAIN"                  # launch cold (OB1.0.0 activity)
```
Then `sleep 4-5` before the first screenshot — Compose + the network boot need a moment.

---

## 4. Screenshots — THE gotcha on this device

`adb exec-out screencap -p > file.png` returns an **all-black** PNG on this Samsung
(~15 KB every time). Use on-device capture + pull instead. Define a helper:

```bash
cap(){ "$ADB" -s "$DEV" shell screencap -p /sdcard/c.png;
       "$ADB" -s "$DEV" pull /sdcard/c.png "$SP/$1" >/dev/null 2>&1;
       echo "$1 = $(stat -c%s "$SP/$1") bytes"; }
# $SP = your scratchpad dir. A real screen is 100KB+; ~15KB still means a black frame.
cap step1.png
```
Requires `MSYS_NO_PATHCONV=1` (§0) or Git-Bash turns `/sdcard/c.png` into
`C:/Program Files/Git/sdcard/...` and the pull fails.

Then **Read** the pulled PNG file to actually see it.

---

## 5. Coordinate system — screenshot px ≠ input px  ⚠️

- The phone is **1080×2340** in portrait (**2340×1080** landscape).
- `adb shell input tap/swipe` uses **real device pixels** (1080-wide).
- When you Read a screenshot, the tool reports e.g. *"original 1080x2340, displayed at
  923x2000, multiply by 1.17"*. That multiplier maps the coords you eyeball on the
  **displayed** image back to **device** pixels.

So: find a target on the displayed screenshot → **multiply x and y by ~1.17** → feed that
to `input tap`. Or just reason directly in device pixels (screen centre = `540,1170`).

---

## 6. Driving the UI

```bash
# TAP  (device px)
"$ADB" -s "$DEV" shell input tap 540 1170

# SWIPE  x1 y1 x2 y2 DURATION_MS   (duration controls speed → fling velocity)
"$ADB" -s "$DEV" shell input swipe 800 1090 350 1090 250     # right→left, ~medium

# TEXT  — spaces are DROPPED by adb; use %s for space, ASCII only
"$ADB" -s "$DEV" shell input text "Replaced%sencoder%scable"

# KEY EVENTS
"$ADB" -s "$DEV" shell input keyevent 4     # BACK  (also dismisses dialogs/panels)
"$ADB" -s "$DEV" shell input keyevent 224   # WAKEUP (screen on)
"$ADB" -s "$DEV" shell input keyevent 66    # ENTER
```

### The MM menu gesture map (what a swipe MEANS)
Portrait (rail is horizontal):
- swipe **left/right** = previous/next category
- swipe **up** = enter the focused category's submenu; **down** = back to menu
- swipe **down on the main menu** = Profile
- overscroll past **far-left** = My Shift; past **far-right** = Search & Scan
- panels close by swiping the OPPOSITE way you entered (no close buttons)

Landscape: the same intents, axes rotated 90° (rail is vertical; enter=left, back=right).

⚠️ Don't start a vertical swipe near the very bottom edge — it triggers the system
home-gesture and drops to the launcher instead of reaching the app.

---

## 7. Reading back what happened (verify, don't assume)

```bash
"$ADB" -s "$DEV" logcat -c                                   # clear BEFORE the action
# ...do the action...
"$ADB" -s "$DEV" logcat -d -b crash | grep "FATAL EXCEPTION" # crashes (0 = good)
"$ADB" -s "$DEV" logcat -d | grep -iE "okhttp"               # HTTP calls the app made
"$ADB" -s "$DEV" logcat -d | grep -oE '"status":"[a-z]+"'    # server responses
```
A Samsung system dialog **"Clear cache for <app>? … has a bug"** = the app crashed; the
stack is in `-b crash`. The app also hides fatals behind a friendly screen, so assert on
`FATAL EXCEPTION` / HTTP 5xx / a black or empty screen — never on "no Fatal error" text.

---

## 8. Test-rig knobs — and put them back

```bash
"$ADB" -s "$DEV" shell svc power stayon true                       # don't sleep mid-test
"$ADB" -s "$DEV" shell settings put system accelerometer_rotation 0
"$ADB" -s "$DEV" shell settings put system user_rotation 0          # 0=portrait, 1=landscape

# ── ALWAYS restore afterwards ──
"$ADB" -s "$DEV" shell settings put system accelerometer_rotation 1
"$ADB" -s "$DEV" shell svc power stayon false
```

---

## 9. One-shot: rebuild → install → relaunch → screenshot

```bash
cd "$PROJ" && ./gradlew.bat :app:assembleDebug --console=plain 2>&1 | grep -iE "^e: |BUILD" \
 && "$ADB" -s "$DEV" install -r app/build/outputs/apk/debug/app-debug.apk \
 && "$ADB" -s "$DEV" shell am force-stop "$PKG" \
 && "$ADB" -s "$DEV" shell am start -n "$MAIN" >/dev/null \
 && sleep 5 && cap after.png
# then Read "$SP/after.png"
```

See also `wcc-companion-build-verify.md` in the agent memory for the terse version.


---

**Sole author: Project owner**

