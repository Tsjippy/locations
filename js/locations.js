function initMap() {
    console.log('Google Maps loaded');
}

document.addEventListener('DOMContentLoaded', () => {
    let map = document.querySelector(".location-map");

    if(map != null){
      document.querySelector('.widget-area.sidebar').prepend(map);
    }
});
            
export function getRoute(target, lat, lon) {
  //Leave the origin empty on a mobile device to use the current location
  if (isMobileDevice()) {
    let origin = "";
  } else {
    let origin = "&origin=" + Location.address;
  }
  let url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}${origin}`;
  let win = window.open(url, "_blank");
  win.focus();
}
