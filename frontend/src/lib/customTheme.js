const STYLE_ID = "aubun-custom-theme";

export const DEFAULT_THEME = Object.freeze({
  primary: "#4d1018",
  secondary: "#6c1823",
  gold: "#feb511",
  goldLight: "#fff1b8",
  goldDark: "#c48d0c",
  cream: "#fef8e4",
  inkMuted: "#8c5a14",
});

const SETTING_KEYS = Object.freeze({
  primary: "themePrimary",
  secondary: "themeSecondary",
  gold: "themeGold",
  goldLight: "themeGoldLight",
  goldDark: "themeGoldDark",
  cream: "themeCream",
  inkMuted: "themeInkMuted",
});

function normalizeHex(value, fallback) {
  if (typeof value !== "string") return fallback;
  let raw = value.trim().toLowerCase();
  if (raw === "") return fallback;
  if (!raw.startsWith("#")) raw = `#${raw}`;
  if (/^#[0-9a-f]{3}$/.test(raw)) {
    raw = `#${raw[1]}${raw[1]}${raw[2]}${raw[2]}${raw[3]}${raw[3]}`;
  }
  if (!/^#[0-9a-f]{6}$/.test(raw)) return fallback;
  return raw;
}

export function resolveTheme(settings) {
  const theme = { ...DEFAULT_THEME };
  let hasCustom = false;

  for (const [key, settingKey] of Object.entries(SETTING_KEYS)) {
    const raw = settings?.[settingKey];
    if (typeof raw === "string" && raw.trim() !== "") {
      hasCustom = true;
      theme[key] = normalizeHex(raw, DEFAULT_THEME[key]);
    }
  }

  return { theme, hasCustom };
}

function hexToRgb(hex) {
  const clean = hex.replace("#", "");
  const num = parseInt(clean, 16);
  return {
    r: (num >> 16) & 255,
    g: (num >> 8) & 255,
    b: num & 255,
  };
}

function rgba(hex, alpha) {
  const { r, g, b } = hexToRgb(hex);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

export function buildThemeCss(theme) {
  return `
:root {
  --primary-black: ${theme.primary};
  --secondary-black: ${theme.secondary};
  --surface-black: ${rgba(theme.primary, 0.72)};
  --border-black: ${rgba(theme.primary, 0.18)};
  --gold: ${theme.gold};
  --gold-light: ${theme.goldLight};
  --gold-dark: ${theme.goldDark};
  --cream: ${theme.cream};
  --ink-muted: ${theme.inkMuted};
  --ink-soft: ${rgba(theme.primary, 0.72)};
  --shadow-xl: 0 30px 80px ${rgba(theme.primary, 0.22)};
  --shadow-lg: 0 20px 50px ${rgba(theme.primary, 0.16)};
  --shadow-md: 0 12px 30px ${rgba(theme.primary, 0.1)};
  --shadow-sm: 0 4px 12px ${rgba(theme.primary, 0.06)};
}
`;
}

export function applyCustomTheme(settings) {
  const { theme, hasCustom } = resolveTheme(settings);
  const existing = document.getElementById(STYLE_ID);

  if (!hasCustom) {
    if (existing) existing.remove();
    return { theme: { ...DEFAULT_THEME }, hasCustom: false };
  }

  const css = buildThemeCss(theme);

  if (existing) {
    existing.textContent = css;
  } else {
    const style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent = css;
    document.head.appendChild(style);
  }

  return { theme, hasCustom: true };
}

export function clearCustomTheme() {
  document.getElementById(STYLE_ID)?.remove();
}
