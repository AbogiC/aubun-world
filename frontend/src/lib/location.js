function readJson(response) {
  if (!response.ok) {
    throw new Error("Location service is unavailable right now.");
  }

  return response.json();
}

const CUSTOMER_LOCATION_KEY = "aubun_customer_location";

function buildAddressLine(parts) {
  return parts.filter(Boolean).join(", ").trim();
}

function readStoredLocation() {
  try {
    return JSON.parse(localStorage.getItem(CUSTOMER_LOCATION_KEY) || "null");
  } catch {
    return null;
  }
}

export function storeCustomerLocation(location) {
  const payload = {
    country: location.country || "",
    city: location.city || "",
    postalCode: location.postalCode || "",
    address: location.address || "",
    latitude: location.latitude ?? null,
    longitude: location.longitude ?? null,
    source: location.source || "unknown",
    updatedAt: new Date().toISOString(),
  };

  localStorage.setItem(CUSTOMER_LOCATION_KEY, JSON.stringify(payload));

  return payload;
}

export function getStoredCustomerLocation() {
  return readStoredLocation();
}

export function getStoredCustomerCountry() {
  return readStoredLocation()?.country || "";
}

export async function resolveCustomerLocationOnLoad() {
  try {
    const position = await getBrowserLocation();
    const location = await reverseGeocode(
      position.coords.latitude,
      position.coords.longitude,
    );
    return location.country || "";
  } catch {
    try {
      const location = await lookupLocationByIp();
      return location.country || "";
    } catch {
      return "";
    }
  }
}

export function getBrowserLocation(options = {}) {
  if (!("geolocation" in navigator)) {
    throw new Error("This browser does not support location access.");
  }

  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 300000,
      ...options,
    });
  });
}

export async function reverseGeocode(latitude, longitude) {
  const params = new URLSearchParams({
    latitude: String(latitude),
    longitude: String(longitude),
    localityLanguage: "en",
  });

  const payload = await fetch(
    `https://api.bigdatacloud.net/data/reverse-geocode-client?${params.toString()}`,
  ).then(readJson);

  const city =
    payload.city ||
    payload.locality ||
    payload.principalSubdivision ||
    payload.localityInfo?.administrative?.[2]?.name ||
    "";

  return storeCustomerLocation({
    source: "geolocation",
    latitude,
    longitude,
    city,
    country: payload.countryName || "",
    postalCode: payload.postcode || "",
    address: buildAddressLine([
      payload.locality,
      payload.principalSubdivision,
    ]),
  });
}

export async function lookupLocationByIp() {
  const payload = await fetch("https://ipapi.co/json/").then(readJson);

  return storeCustomerLocation({
    source: "ip",
    latitude: payload.latitude ?? null,
    longitude: payload.longitude ?? null,
    city: payload.city || "",
    country: payload.country_name || "",
    postalCode: payload.postal || "",
    address: buildAddressLine([payload.region, payload.country_name]),
  });
}
