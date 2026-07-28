# Repomix analysis — authorship scrub

**Sole author:** Project owner  
**Wall file:** `ai_ctxt/REPOMIX_SOURCE_WALL.txt` (generated; **originals not deleted**)  
**Generated size (this run):** ~876 KB · **112 files** concatenated  

## What was concatenated

| Group | Contents |
|-------|----------|
| Meta | `ai_agent.ini`, `AUTHOR.md` |
| Context | All `ai_ctxt/*.md` (except the wall itself) |
| Build | `app/build.gradle.kts` |
| Manifest / XML | `AndroidManifest.xml`, `res/xml/*`, `values/strings.xml` |
| Source | All `app/src/main/java/**/*.kt` |
| Tools | `tools/*.py` |

**Not** concatenated (kept on disk, not in wall): `build/`, QA PNGs, APKs, `.gradle`, local.properties secrets.

## Pattern scan (post-update)

| Pattern | Hits | Verdict |
|---------|-----:|---------|
| Co-author / “co-authors” | ~9 | **Policy language only** (“none”, “never add…”) |
| “Created by” | 1 | **Forbidden-pattern example** in AUTHORSHIP.md |
| Claude / Gemini / OpenAI / Anthropic / ChatGPT / Copilot / Aider as authors | 0 | Clean |
| Foreign `@author` tags in Kotlin | 0 | Clean |
| Multi-party Copyright headers | 0 | Clean |

## Actions taken

1. Established **sole author = Project owner** in `AUTHOR.md`, `ai_ctxt/AUTHORSHIP.md`, `ai_agent.ini`, all status docs.  
2. Scrubbed agent bootstrap wording so AI products are **tools**, not authors.  
3. Regenerated living status for **Open Beta 1.0.0**.  
4. Built **new** wall file without removing working sources.  

## Conclusion

No third-party human co-authors or AI-as-author attributions remain in the concatenated project text.  
**The project owner is the sole author.**

To regenerate the wall after future edits:

```powershell
# From project root — see tools/rebuild_repomix.ps1
powershell -File tools/rebuild_repomix.ps1
```
