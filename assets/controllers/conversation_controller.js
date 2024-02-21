import { Controller }  from "@hotwired/stimulus";
import { processFetchPOSTInit } from "../js/service/basicStuffService";

export default class extends Controller {
    static values = {
        url:  String,
        url1: String,
        url2: String
    };

    static targets = ['result', 'groupConversationsList', 'conversationsList'];

    async mercureEventSource(event) {
        let topics      = [];
        let scriptTagId = document.getElementById('mercureScriptTagId');

        for (const option of event.currentTarget.options) {
            if (option.selected) {
                topics.push(`notifications${ option.value }`);
            }
        }

        console.log(topics)

        const response = await fetch(
            this.url1Value,
            processFetchPOSTInit({data: {
                'topics':      topics,
                'scriptTagId': scriptTagId.value
            }})
        );

        this.resultTarget.innerHTML = await response.text();
    }

    async searchConversation(event) {
        const conversationType   = document.getElementById('search_form_conversationType');
        const activeConversation = document.getElementById('search_form_activeConversation');
        const params             = new URLSearchParams({
            q:       event.currentTarget.value,
            preview: 1,
            type:    conversationType.value,
            convId:  activeConversation.value
        });

        const respone = await fetch(`${this.urlValue}?${params.toString()}`);
        console.log(`${this.urlValue}?${params.toString()}`);
        this.conversationsListTarget.innerHTML = await respone.text();
    }

    async createGroup(event) {
        const respone = await fetch(`${this.url1Value}`);
        this.resultTarget.innerHTML = await respone.text();
    }
}