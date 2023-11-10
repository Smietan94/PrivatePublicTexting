import { Controller }  from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url:  String,
        url1: String
    };

    static targets = ['result', 'friendsList'];

    async chooseConversation(event) {
        const params = new URLSearchParams({
            q:       event.currentTarget.value,
            preview: 1
        });

        const respone = await fetch(`${this.urlValue}?${params.toString()}`);
        this.resultTarget.innerHTML = await respone.text();
    }

    async createGroup(event) {
        const respone = await fetch(`${this.url1Value}`);
        this.resultTarget.innerHTML = await respone.text();
    }
}