import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
    };

    static targets = ['result'];

    // handles user search
    async onSearchInput(event) {
        const params = new URLSearchParams({
            q:       event.currentTarget.value,
            preview: 1,
        });

        const response = await fetch(`${this.urlValue}?${params.toString()}`);
        this.resultTarget.innerHTML = await response.text();
    }
}