---
name: ai-assistant-integration
description: Integrate a privacy-first conversational AI assistant into a Laravel app — multi-provider support (Claude, GPT, Gemini, Mistral, DeepSeek, self-hosted Ollama) behind one AiClient contract, write-only encrypted API keys, allow-listed read-only server tools instead of database access, per-user quotas, encrypted conversation storage with retention pruning, and off-by-default privacy posture. Use this whenever the user wants an AI assistant/chatbot inside their app, "ask questions about my data", LLM integration, or AI features in a regulated/personal-data context.
---

# AI assistant integration (privacy-first)

The architecture is the product here: the model never touches the database, credentials are unreadable once saved, and the default posture is *closed*. Build the guardrails first, the chat UI second.

## Architecture

1. **One contract, N providers** — an `AiClient` interface (`chat(AiRequest): AiReply`, `testConnection()`); one adapter per provider; an enum lists providers with their **data-residency notes** displayed in settings (an informed admin choice, not fine print). Include an Ollama/local adapter: it makes "no outbound calls" a selectable reality, and lets the assistant answer even when the cloud switch is off.
2. **Settings panel** (reuse `app-settings-branding` machinery): provider, model, base URL (EU gateway or local instance), API key stored `encrypted: true` and rendered **write-only**, generation caps, per-user daily quota, conversation retention days. Feature is **disabled by default**; personal-data context for the model is a separate, also-default-off switch.
3. **Tools, not database access** — the assistant answers from an **allow-list of server-side read-only tools** (e.g. stock alerts, catalog lookup, help pages). Each tool: an explicit permission scope, bounded output size, no free-form SQL, no personal data unless that separate switch (and your compliance review) says so. The allow-list is a PHP registry — adding a tool is a code review, not a config edit.
4. **Conversations** — encrypted at rest, scoped per account, pruned by a scheduled `ai:prune` per the retention setting. A per-user daily quota protects the bill and the temptation to paste sensitive data all day.
5. **UI** — floating button + full-screen page; label clearly as beta with scope stated ("answers from app data X, Y — verify important answers").

## Verification checklist

- [ ] Saved API key never appears in any response, log, or page source
- [ ] With the feature off, zero outbound AI calls (assert in tests via a fake client)
- [ ] Each tool refuses callers lacking its permission; outputs are bounded
- [ ] Conversations of user A invisible to user B; pruning enforces retention

## Reference implementation

OrthoZ: `app/Contracts/AiClient.php`, `app/Services/Ai/`, `app/Data/Ai/`, settings at `/reglages#ai`, `docs/ARCHITECTURE-IA.md` (the residency + level-1/level-2 scoping rationale).
