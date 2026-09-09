# WordPress / Gutenberg

Custom theme files for the production WordPress installation live in
`wp-content/themes/belye-nochi`.

On first activation the theme creates two published pages from the approved
static markup:

- `Главная` (`/`) — eight section-level Gutenberg Custom HTML blocks.
- `Номера` (`/rooms/`) — three section-level Gutenberg Custom HTML blocks.

The activation import runs only when the pages do not exist, so later editor
changes are never overwritten automatically.
