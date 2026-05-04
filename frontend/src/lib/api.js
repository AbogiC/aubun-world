const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";
const TOKEN_KEY = "aubun_auth_token";
const CUSTOMER_LOCATION_KEY = "aubun_customer_location";

export function resolveAssetUrl(path) {
  if (!path) return "";

  if (/^https?:\/\//i.test(path) || path.startsWith("data:")) {
    return path;
  }

  if (path.startsWith("//")) {
    return `${window.location.protocol}${path}`;
  }

  return new URL(path, window.location.origin).toString();
}

export function getAuthToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function setAuthToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
    return;
  }

  localStorage.removeItem(TOKEN_KEY);
}

function getCustomerCountry() {
  try {
    const location = JSON.parse(localStorage.getItem(CUSTOMER_LOCATION_KEY) || "null");
    return location?.country || "";
  } catch {
    return "";
  }
}

async function request(path, options = {}) {
  const token = getAuthToken();
  const headers = {
    ...options.headers,
  };

  if (!(options.body instanceof FormData) && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const country = getCustomerCountry();

  if (country) {
    headers["X-Customer-Country"] = country;
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    const error = new Error(payload.message || "API request failed.");
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload;
}

export const api = {
  get: (path) => request(path),
  post: (path, body) =>
    request(path, {
      method: "POST",
      body: body instanceof FormData ? body : JSON.stringify(body),
    }),
  patch: (path, body) =>
    request(path, {
      method: "PATCH",
      body: JSON.stringify(body),
    }),
  delete: (path) =>
    request(path, {
      method: "DELETE",
    }),
};
