```markdown
# Design System Document

## 1. Overview & Creative North Star: "The Architectural Terminal"

This design system is built for the intersection of high-end brand strategy and technical execution. The Creative North Star is **"The Architectural Terminal"**—a digital environment that rejects the "softness" of consumer web trends in favor of the precision, authority, and raw speed associated with a developer’s command line.

We move beyond a "hacker" cliché by employing a high-end editorial lens: intentional asymmetry, dramatic typographic scaling, and tonal depth. We are not just building a website; we are building a mission-control interface for tech founders. The aesthetic is sharp, disciplined, and unapologetically digital.

---

## 2. Colors & Surface Logic

The palette is rooted in absolute blacks and high-frequency neons. However, to achieve a premium feel, we must avoid "flatness."

### The Palette (Token Mapping)

- **Primary (`#00FFB8`):** Use for "System Success" and primary actions. It represents the "Go" signal.
- **Secondary (`#00D9FF`):** Use for data visualization, highlights, and interactive accents.
- **Surface Layering:**
  - `surface`: `#0A0A0A` (The void)
  - `surface-container-low`: `#131313` (Section background)
  - `surface-container-high`: `#201f1f` (Elevated components)

### The "No-Line" Rule

Prohibit 1px solid borders for sectioning. Boundaries must be defined solely through background color shifts. For example, a `surface-container-low` section sitting against a `surface` background creates a hard, structural edge without the "cheapness" of a stroke. Use the **Grid Background** (a 24px subtle line grid) to provide the eye with structural anchors.

### The "Glass & Noise" Rule

To move beyond standard UI, every surface must feel "material."

- **Noise:** Apply a 3% opacity grain overlay to all `surface-container` tiers.
- **Glow Orbs:** Use large, blurred radial gradients of `primary_dim` and `secondary_dim` at 5% opacity in the background to create atmospheric depth. These should feel like light leaking from a high-resolution CRT monitor.

---

## 3. Typography: Monospaced Authority

We utilize **IBM Plex Mono** for all text. This is non-negotiable. It reinforces the "Terminal" aesthetic while remaining highly legible for long-form brand strategy.

### Typographic Hierarchy

- **Display Large (3.5rem):** Use for high-impact hero statements. Letter-spacing should be set to `-0.02em` to keep the monospaced characters from feeling too gapped.
- **Headline Medium (1.75rem):** Use for section headers. All-caps for a "System Protocol" feel.
- **Body Medium (0.875rem):** The workhorse. Ensure a generous line-height (`1.6`) to prevent monospaced text from becoming a "block" of unreadable code.
- **Label Small (0.6875rem):** Used for metadata, breadcrumbs, and status indicators. Always pair with an underscore prefix (e.g., `_STATUS: ACTIVE`).

---

## 4. Elevation & Depth: Tonal Stacking

In this system, depth is not achieved by mimicry of the physical world, but by "Digital Stacking."

### The Layering Principle

Depth is achieved by stacking surface-container tiers. Place a `surface-container-highest` card on a `surface-container-low` section. This creates a soft, natural lift.

### Ambient Glows (The Shadow Alternative)

Traditional drop shadows are forbidden. Instead, use "Ambient Glows." When a component is "active" or "hovered," it should emit a subtle `0px 0px 15px` glow using the `primary` token at 20% opacity.

### The "Ghost Border" Fallback

If a container requires a border for accessibility, use the **Ghost Border**: the `outline-variant` token at 15% opacity. It should be barely visible—a "whisper" of a boundary that only appears when the user focuses on the element.

---

## 5. Components

### Buttons (The "Execution" Units)

- **Primary:** Sharp corners (`0px`). Background: `primary`. Text: `on_primary`.
- **Effect:** A three-layer box-shadow:
  1. `inset 0 0 8px #ffffff80` (Highlight)
  2. `0 0 12px #00FFB840` (Primary Glow)
  3. `4px 4px 0px #000000` (Hard Offset)
- **Interaction:** On hover, use a spring-based scale (`scale(1.02)`) with a `cubic-bezier(0.175, 0.885, 0.32, 1.275)` transition.

### Input Fields (The "Terminal" Prompt)

- **Style:** No background. A single bottom-border (`outline-variant`).
- **Prefix:** Every input must be preceded by a `>` cursor.
- **State:** When focused, the `>` should blink (0.5s interval).

### Cards

- **Construction:** Use `surface-container-high` with 0px border-radius.
- **Prohibition:** No divider lines. Use vertical white space (Token `8` or `10`) to separate content blocks.
- **Signature Touch:** Add a small "Scanline" overlay (repeated 2px linear gradient) at 2% opacity across the card face to simulate a screen.

### Navigation (The "Directory")

- **Layout:** Intentional asymmetry. Place the main logo in the top-left and navigation items grouped in the bottom-right or center-right, mimicking a directory structure (e.g., `01_Work / 02_Process / 03_Deploy`).

---

## 6. Do's and Don'ts

### Do:

- **Use All-Caps for Labels:** Use `label-sm` in all-caps to signify "System Data."
- **Embrace Asymmetry:** Let sections "float" on the grid. Not everything needs to be centered.
- **Use Typing Effects:** For hero headlines, use a typing animation (one character at a time) to lean into the terminal vibe.

### Don't:

- **No Rounded Corners:** Any radius above `0px` breaks the technical precision of the system.
- **No Traditional Gradients:** Avoid "sunset" or soft gradients. Use "Digital Gradients" (e.g., `primary` to `primary_container` hard-stops or subtle noise-dithered transitions).
- **No Icons without Purpose:** Only use icons if they serve a functional, terminal-like role (e.g., `[+]`, `[-]`, `->`). Avoid illustrative, rounded iconography.

### Accessibility Note:

While we use a dark aesthetic, ensure all `muted-text` passes WCAG AA contrast ratios against `surface-container-low`. High-frequency neons (`primary`) must be used sparingly for text to avoid eye strain.
```
