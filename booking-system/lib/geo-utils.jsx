// Haversine algorithm to calculate distance between two coordinates
export function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 3959 // Earth's radius in miles
  const dLat = ((lat2 - lat1) * Math.PI) / 180
  const dLon = ((lon2 - lon1) * Math.PI) / 180
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  const distance = R * c
  return Math.round(distance * 10) / 10
}

// Get nearby providers within specified radius
export function getNearbyProviders(providers, userLat, userLon, radiusMiles = 10, service = "all") {
  const nearby = providers
    .filter((p) => {
      const distance = calculateDistance(userLat, userLon, p.latitude, p.longitude)
      const matchesService = service === "all" || p.service === service
      return distance <= radiusMiles && matchesService && p.status === "active"
    })
    .map((p) => ({
      ...p,
      distance: calculateDistance(userLat, userLon, p.latitude, p.longitude),
    }))
    .sort((a, b) => a.distance - b.distance)

  return nearby
}

// Generate Google Maps embed URL
export function getGoogleMapsUrl(lat, lon, providerName) {
  return `https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d${lon}!3d${lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0:0x0!2z${lat},${lon}!5e0!3m2!1sen!2sus!4v1234567890`
}
