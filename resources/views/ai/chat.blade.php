@extends('layouts.app')

@section('title', 'AI Chat Assistant — ' . config('app.name'))

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">AI Chat Assistant</h1>
            <p class="page-subtitle">Ask questions about your live security data. Administrator access only.</p>
        </div>
        <div class="row-actions">
            <a href="{{ route('ai.report') }}" class="btn btn-secondary" target="_blank" rel="noopener">Print Report</a>
            <a href="{{ route('ai.dashboard') }}" class="btn btn-ghost">Back to AI Dashboard</a>
        </div>
    </div>

    <section class="panel">
        <div class="ai-chat" id="ai-chat" aria-live="polite">
            <div class="ai-msg ai-msg-bot">
                <span class="avatar avatar-md" aria-hidden="true">✦</span>
                <div class="ai-bubble">
                    Hello — I'm the AI Security Assistant. I answer from the live database, so everything I tell you reflects the system right now. Try one of the suggestions below, or type "help".
                </div>
            </div>
        </div>

        <div class="ai-suggestions" id="ai-suggestions">
            <button type="button" class="btn btn-ghost">Show today's critical alerts</button>
            <button type="button" class="btn btn-ghost">Which cameras are offline?</button>
            <button type="button" class="btn btn-ghost">List unknown faces detected today</button>
            <button type="button" class="btn btn-ghost">Show suspicious employees</button>
            <button type="button" class="btn btn-ghost">Summarize today's security events</button>
            <button type="button" class="btn btn-ghost">Generate today's security report</button>
        </div>

        <form id="ai-chat-form" class="ai-chat-input">
            @csrf
            <input type="text" id="ai-chat-message" name="message" maxlength="500" autocomplete="off"
                   placeholder="Ask about alerts, cameras, visitors, employees…" aria-label="Message the AI assistant">
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </section>

@endsection

@push('scripts')
<script>
    (function () {
        const chat = document.getElementById('ai-chat');
        const form = document.getElementById('ai-chat-form');
        const input = document.getElementById('ai-chat-message');
        const token = form.querySelector('input[name="_token"]').value;
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        function append(role, html) {
            const wrap = document.createElement('div');
            wrap.className = 'ai-msg ' + (role === 'user' ? 'ai-msg-user' : 'ai-msg-bot');
            wrap.innerHTML = `
                <span class="avatar avatar-md" aria-hidden="true">${role === 'user' ? '◉' : '✦'}</span>
                <div class="ai-bubble">${html}</div>`;
            chat.appendChild(wrap);
            chat.scrollTop = chat.scrollHeight;
            return wrap;
        }

        function rowsToTable(rows) {
            if (!rows || !rows.length) return '';
            const headers = Object.keys(rows[0]);
            return `<table><thead><tr>${headers.map(h => `<th>${esc(h)}</th>`).join('')}</tr></thead>
                <tbody>${rows.map(r => `<tr>${headers.map(h => `<td>${esc(r[h] ?? '—')}</td>`).join('')}</tr>`).join('')}</tbody></table>`;
        }

        async function send(message) {
            append('user', esc(message));
            const typing = append('bot', '<span class="ai-typing">Analyzing…</span>');

            try {
                const res = await fetch('{{ route('ai.chat.message') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' },
                    body: JSON.stringify({ message }),
                });
                const data = await res.json();
                typing.querySelector('.ai-bubble').innerHTML = res.ok
                    ? esc(data.reply) + rowsToTable(data.rows)
                    : 'Sorry, I could not process that request.';
            } catch (_) {
                typing.querySelector('.ai-bubble').textContent = 'Connection problem — please try again.';
            }
            chat.scrollTop = chat.scrollHeight;
        }

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const message = input.value.trim();
            if (!message) return;
            input.value = '';
            send(message);
        });

        document.getElementById('ai-suggestions').addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (btn) send(btn.textContent.trim());
        });
    })();
</script>
@endpush
