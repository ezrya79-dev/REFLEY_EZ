<x-layout.public page="accueil" :title="__('content.pageAccueil')">
    <section class="hero">
        <h1><x-content page="accueil" key="hero.titre">Votre équipe, un seul espace</x-content></h1>
        <x-content-markdown page="accueil" key="hero.corps">
Refley réunit les comptes, les rôles et l'espace personnel de votre équipe dans une application **simple, bilingue et à vos couleurs**.
        </x-content-markdown>
        <x-content-image page="accueil" key="hero.visuel" class="hero-visual" sizes="(min-width: 900px) 50vw, 100vw" />
        <a href="{{ route('login') }}" class="btn btn-primary">{{ __('auth.login') }}</a>
    </section>

    <section class="feature-grid">
        <div class="card"><div class="card-body">
            <h3><x-content page="accueil" key="atout1.titre">Comptes et rôles</x-content></h3>
            <x-content-markdown page="accueil" key="atout1.corps">
Des permissions fines, un anti-verrouillage intégré, et une matrice lisible par tous.
            </x-content-markdown>
        </div></div>
        <div class="card"><div class="card-body">
            <h3><x-content page="accueil" key="atout2.titre">À vos couleurs</x-content></h3>
            <x-content-markdown page="accueil" key="atout2.corps">
Nom, palette et logo se changent depuis les réglages — sans déploiement.
            </x-content-markdown>
        </div></div>
        <div class="card"><div class="card-body">
            <h3><x-content page="accueil" key="atout3.titre">Roadmap partagée</x-content></h3>
            <x-content-markdown page="accueil" key="atout3.corps">
Chacun propose, vote et suit les évolutions du produit, module par module.
            </x-content-markdown>
        </div></div>
    </section>
</x-layout.public>
