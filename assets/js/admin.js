document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('ai-wp-assistant-form');
    const input = document.getElementById('ai-wp-assistant-input');
    const messages = document.getElementById('ai-wp-assistant-messages');

    if (!form || !input || !messages) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const message = input.value.trim();

        if (!message) {
            return;
        }

        addMessage('user', message);

        input.value = '';

        const button = form.querySelector('button');
        button.disabled = true;

        try {
            const response = await fetch(AIWPAssistant.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': AIWPAssistant.nonce,
                },
                body: JSON.stringify({
                    message,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(
                    data.message || AIWPAssistant.i18n.error
                );
            }

            addMessage('assistant', data.message);
        } catch (error) {
            addMessage(
                'assistant',
                error.message || AIWPAssistant.i18n.error
            );
        } finally {
            button.disabled = false;
            input.focus();
        }
    });

    function addMessage(role, message) {
        const wrapper = document.createElement('div');

        wrapper.className =
            `ai-wp-assistant__message ai-wp-assistant__message--${role}`;

        const label = document.createElement('strong');

        label.textContent =
            role === 'user' ? 'You:' : 'Assistant:';

        const content = document.createElement('p');

        content.textContent = message;

        wrapper.appendChild(label);
        wrapper.appendChild(content);

        messages.appendChild(wrapper);

        messages.scrollTop = messages.scrollHeight;
    }
});