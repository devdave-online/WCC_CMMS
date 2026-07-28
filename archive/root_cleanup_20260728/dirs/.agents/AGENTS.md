# AI Agent Rules

### Web UI Screenshot Resolution Guardrail
When using the `browser_subagent` (or any tool) to capture screenshots of web applications for documentation or presentation:
- **NEVER** capture at the user's default native resolution if they are using an ultrawide monitor (e.g. 3440x1305).
- **ALWAYS** explicitly instruct the subagent in its task prompt to resize its viewport to a standard, compact resolution (e.g., `1200x800` or `1280x720`) BEFORE navigating or taking screenshots.
- This ensures that responsive layouts condense appropriately, eliminating dead space and keeping UI text crisp and readable when the screenshot is scaled down to fit within documentation columns.
