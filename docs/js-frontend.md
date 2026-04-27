# JavaScript Frontend

## `src/js/backend.js` → `build/js/backend.js`

React single-page app mounted on `<div id="acwpms-settings-root">`.

**WP script dependencies:** `react-jsx-runtime`, `wp-api-fetch`, `wp-components`, `wp-element`, `wp-i18n`

## `window.acaiModelManagerSettings` (set via `wp_localize_script`)

```js
{
  models: {
    // Empty object ({}) when AI plugin is not active.
    text_generation:  { provider_id: { label: string, models: [{ value, label }] } },
    image_generation: { ... },
    vision:           { ... }
  },
  preferences: {
    text_generation:    'provider::model_id',  // or ''
    image_generation:   'provider::model_id',
    vision:             'provider::model_id',
    request_timeout:    30,         // int|null
    temperature:        0.7,        // float|null (UI hidden)
    max_tokens:         1024,       // int|null   (UI hidden)
    top_p:              null,
    top_k:              null,
    presence_penalty:   null,
    frequency_penalty:  null,
  },
  nonce: '<wp_rest nonce>',
  optionName: 'acai_model_manager_preferences',
  aiPluginActive: true  // false when AI plugin not installed/active
}
```

**Model value format:** `"provider_id::model_id"` (double-colon separator)

**Capability mapping:** `text_generation` from AiClient maps to both `text_generation` and `vision` groups.

## Components

**`SettingsApp`** — main component
- State: `preferences` (object), `isSaving` (bool), `notice` (`{type, message}|null`)
- **Card 1: Model Preferences** — 3 `<select>` dropdowns
  - `aiPluginActive: false` → `<Notice status="warning">` + all selects `disabled={true}`
  - `aiPluginActive: true` → normal operation
- **Card 2: Generation Parameters** — **HIDDEN** (`{ false && (...) }`), 6 number inputs
- **Card 3: Request Settings** — `request_timeout` number input; always enabled
- Save button always enabled (HTTP timeout works without AI plugin)

**`mount()`** — targets `#acwpms-settings-root`; uses React 18 `createRoot()` with legacy `render()` fallback.

## SCSS — `src/scss/backend.scss`

**CSS class prefix:** `.acwpms-`

| Selector | Purpose |
|---|---|
| `#acwpms-settings-root` | Mount point — `margin-top: 20px` |
| `.acwpms-settings-app` | React app wrapper — max-width 720px |
| `.acwpms-card` | Card reset — removes default top margin |
| `.acwpms-params-card` | Second/third card — `margin-top: 16px` |
| `.acwpms-param-input` | Number input — 160px wide, 36px tall |
| `.acwpms-provider-select` | Model `<select>` — full width (max 480px), 36px tall |
| `.acwpms-save-row` | Save button row — `margin-top: 16px` |

## Build Commands

```bash
npm run start             # Dev build + file watcher
npm run build             # Production build
npm run build-production  # NODE_ENV=production (used by CI)
npm run plugin-zip        # Creates acrossai-model-manager.zip
npm run lint:js           # ESLint
npm run lint:css          # Stylelint
```

**Webpack** (`webpack.config.js`): extends `@wordpress/scripts`. Entry points: `src/js/backend.js` and `src/scss/backend.scss`. Uses `RemoveEmptyScriptsPlugin` + `CopyPlugin` for media assets.
