import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        lat: Number,
        lng: Number,
        label: String,
    };

    connect() {
        if (typeof L === 'undefined') {
            return;
        }

        const map = L.map(this.element).setView([this.latValue, this.lngValue], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        L.marker([this.latValue, this.lngValue])
            .addTo(map)
            .bindPopup(this.labelValue)
            .openPopup();
    }
}
