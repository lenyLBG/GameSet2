Copilot instructions for GameSet2

This file gives focused, actionable guidance so an AI coding agent can be immediately productive in this Symfony-based codebase.

Key pieces of the project
- Framework: Symfony (controllers under `src/Controller`, services under `src/Service`, Twig templates under `templates/`).
- Domain entities: `Tournoi`, `Rencontre`, `Equipe` live under `src/Entity` and drive most flows (brackets, participants, scores).
- Bracket logic: `src/Service/BracketGenerator.php` generates transient bracket structures and can persist `Rencontre` entities via `persistMatches()`.
- Main UI: tournament detail and bracket rendering in `templates/tournois/show.html.twig`. JavaScript in that template posts scores to `app_rencontre_score` and uses Bootstrap modals.

Conventions & important patterns
- Controllers often accept `ManagerRegistry $doctrine` and use repositories (`$doctrine->getRepository(...)`) or the entity manager. Prefer mirroring existing patterns.
- Authorization checks are consistently: only the tournoi creator or `ROLE_ADMIN` can mutate brackets, scores, participants. Search for checks like `(app.user == tournoi.creator or is_granted('ROLE_ADMIN'))` in templates and the same logic in controllers.
- CSRF tokens: forms use `csrf_token()` with custom keys. Reuse the same token names when creating forms (examples: `set_match_winner_{{ m.id }}`, `score_rencontre_{{ rencontre.id }}`, `generate_bracket_{{ tournoi.id }}`).
- Routes are defined with PHP attributes in controllers (e.g. `#[Route('/rencontre/{id}/score', name: 'app_rencontre_score', methods: ['POST'])]`). New routes should follow the naming convention `app_<resource>_<action>`.
- Brackets: controller `TournoisController::show()` prefers persisted `Rencontre` rows; otherwise it uses `BracketGenerator->generate()` to render a transient structure. When adding features related to brackets, account for both persisted and transient shapes.

Developer workflows (commands you can run)
- Install/update PHP deps: `composer install` / `composer update`.
- Clear cache: `php bin/console cache:clear` (used frequently during template/controller edits).
- Run migrations: `php bin/console doctrine:migrations:migrate` (migrations live in `migrations/`).
- Run tests: use the repository's PHPUnit wrapper `bin/phpunit` or `vendor/bin/phpunit`. See `phpunit.dist.xml`.
- Quick syntax checks: `php -l src/...` to lint PHP files.

Where to implement "designate match winner" changes
- Primary controller: `src/Controller/TournoisController.php` contains existing endpoints: `app_rencontre_set_winner` and `app_rencontre_reset_winner`. Use these for server-side updates and preserve CSRF + auth checks.
- Template: `templates/tournois/show.html.twig` contains `winner_form` macro and where the selector should appear in each bracket view. If the selector doesn't appear, ensure the macro is called for both persisted matches (which include `a_id`/`b_id`) and generator-produced matches (which may only include `a`/`b` strings).
- Persistence: `src/Service/BracketGenerator::persistMatches()` creates `Rencontre` rows when a bracket is saved. If you add features that assume `a_id`/`b_id`, ensure `persistMatches()` populated those fields.

Integration points & gotchas
- Front-end posting: score submissions use a fetch POST to `app_rencontre_score` (see the `scoreModal` script). Ensure CSRF token presence (token is usually injected in forms via Twig). The template may read a meta tag for CSRF — prefer posting token via form inputs rendered by Twig.
- Data shapes differ:
  - Persisted matches (from DB) include `id`, `a`, `a_id`, `b`, `b_id`, `score_a`, `score_b`, `status`, `position`, `winner_id`.
  - Generated (transient) matches from `BracketGenerator->generate()` often include only `a`/`b` display values (strings) or team arrays. Template code must guard access with `is defined` checks before reading `a_id`/`b_id`.
- Business logic is implemented in controllers: e.g. setting a winner advances teams in single/double elimination formats. If you move logic elsewhere, mirror the advancement rules.

Testing & adding features
- Add functional tests under `tests/` when changing endpoints. Use `bin/phpunit` and mirror existing test bootstrap in `tests/bootstrap.php`.
- When adding UI changes to `templates/tournois/show.html.twig`, clear the cache and test both persisted and freshly generated brackets to ensure both flows render.

Quick examples
- Check if a match has team IDs before rendering winner selector:
  `{% if m.a_id is defined and m.b_id is defined and m.a_id and m.b_id %}`
- Posting a manual winner (controller route): `POST /rencontre/{id}/set-winner/{equipeId}` — ensure `_token` uses `csrf_token('set_match_winner_' ~ id)`.

If something is unclear or you want me to expand any section (example unit tests, a sample functional test, or converting controller logic into a service), tell me which part and I'll iterate.
