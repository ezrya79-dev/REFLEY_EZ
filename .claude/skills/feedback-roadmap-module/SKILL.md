---
name: feedback-roadmap-module
description: Add an in-app product roadmap where every user proposes ideas, votes and comments, while status/priority/difficulty arbitration stays permission-gated — plus an optional deployments board showing GitHub PRs and releases. Use this whenever the user wants feature requests, user feedback collection, an ideas board, voting on improvements, or visibility on "what's coming" inside their app.
---

# Feedback & roadmap module

Feature requests belong **inside the app** where the users live, not in a shared doc. Contribution is open to everyone; arbitration is a permission.

## Method

1. **Model** — `FeatureRequest` (title, description, status enum: proposed/accepted/in-progress/shipped/declined, priority enum, difficulty enum, author), `FeatureVote` (unique per user+request), `FeatureComment`. Statuses/priorities as PHP enums with label keys — they render in the UI and sort meaningfully.
2. **Open contribution** (`roadmap.view`): any authenticated user proposes, votes (toggle, one per idea), comments. Show vote counts and sort by them by default — the point is surfacing consensus.
3. **Gated arbitration** (`roadmap.manage`): only the owner role changes status/priority/difficulty or deletes others' ideas. Every arbitration is audited — "who declined my idea" must have an answer.
4. **Lifecycle honesty** — when something ships, flip it to shipped and let `onboarding-whats-new`'s release notes reference it. A roadmap where nothing ever moves is worse than none; wire the statuses into the team's actual workflow.
5. **Optional deployments board** (admin): a Kanban of the repo's PRs and releases via a **write-only GitHub token** stored encrypted in settings (`app-settings-branding` pattern), read-only API calls, cached — the non-technical owner sees delivery flowing without opening GitHub.

## Verification checklist

- [ ] Double-vote impossible (DB unique constraint, not just UI)
- [ ] Non-managers get 403 on status/priority changes; changes audited
- [ ] Deleting an idea cascades votes/comments cleanly
- [ ] GitHub token never readable after save; board degrades gracefully offline

## Reference implementation

OrthoZ: `/livraisons` (roadmap), `/livraisons/deploiements` (GitHub Kanban), `app/Models/Feature*`, `app/Contracts/GithubClient.php`, `app/Data/Github/`.
