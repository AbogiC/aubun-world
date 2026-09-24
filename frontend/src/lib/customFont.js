const STYLE_ID = "aubun-custom-font";
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";

function resolveFontUrl(settings) {
  const filename = settings?.customFontFilename;
  if (filename) {
    const cleanBase = API_BASE_URL.replace(/\/$/, "");
    return `${cleanBase}/fonts/${encodeURIComponent(filename)}`;
  }
  return settings?.customFontUrl || "";
}

function cssEscape(str) {
  return String(str).replace(/\\/g, "\\\\").replace(/'/g, "\\'").replace(/\n/g, " ");
}

export function applyCustomFont(settings) {
  const existing = document.getElementById(STYLE_ID);
  const url = resolveFontUrl(settings);
  const family = (settings?.customFontFamily || "").trim();

  if (!url || !family) {
    if (existing) existing.remove();
    return;
  }

  const safeFamily = cssEscape(family);
  const safeUrl = url.replace(/"/g, "%22");

  const css = `
@font-face {
  font-family: '${safeFamily}';
  src: url("${safeUrl}") format('truetype');
  font-weight: 400 700;
  font-style: normal;
  font-display: swap;
}
body, button, input, select, textarea, .btn, .form-control, .form-select {
  font-family: '${safeFamily}', "Alcubierre", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
}
h1, h2, h3, h4, h5, h6,
.display-1, .display-2, .display-3, .display-4, .display-5 {
  font-family: '${safeFamily}', "Sivana", Georgia, "Times New Roman", serif !important;
}
`;

  if (existing) {
    existing.textContent = css;
    return;
  }

  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = css;
  document.head.appendChild(style);
}

export function clearCustomFont() {
  document.getElementById(STYLE_ID)?.remove();
}
