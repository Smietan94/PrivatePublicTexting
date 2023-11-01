import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
    };

    static targets = ['result'];

    async hello(event) {
        const params = new URLSearchParams({
            q:       event.currentTarget.value,
            preview: 1
        });

        const respone = await fetch(`${this.urlValue}?${params.toString()}`);
        this.resultTarget.innerHTML = await respone.text();
    }
}