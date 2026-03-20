=== Gerar Ficha Técnica ===
Contributors: mozbeats
Tags: technical sheet, artists, seo, auto tags, shortcode
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic generator of music technical sheets with smart artist detection, genres, SEO, and conversion of shortcode to permanent HTML.

== Description ==

Generate Music Technical Sheet is an advanced WordPress plugin designed for music blogs that fully automates the creation of technical sheets based on the post title.

The plugin uses an intelligent parsing system to identify artists, features (feat.), year, genre, and automatically generate a professional technical sheet.

Starting from version 1.3.0, the plugin introduces:

* Artist gender identification (Male, Female, or Group)
* Conversion of shortcode into permanent HTML (improves SEO and performance)
* Restoration system with temporary backup (24h)
* Artist management interface in the dashboard

== Main Features ==

🎤 Smart Artist Detection

* Automatically detects the main artist
* Supports multiple artists (comma, &, etc.)
* Automatically fixes grammar
* Removes old tags when updating the title

🎧 Feature Detection (feat.)

* Supports (feat.) and (ft.)
* Multiple guest artists
* Correct usage of "and" before the last name

🏷️ Smart Tag System

Automatically creates tags for:

* Main artist
* Guest artists
* Year of publication

✔ Automatically updates when editing the title
✔ Keeps manual tags intact
✔ Removes old tags automatically

📅 Automatic Year Tag

* Creates a tag with the post year
* Updates if the date changes
* Automatically removes old years

🎼 Music Genre System

* List of allowed genres
* Ability to add custom genres
* Warning if the post has no valid genre

👤 Artist Classification (NEW)

Now you can define the artist type directly in the dashboard:

* Male
* Female
* Group / Label

This allows generating automatic texts like:

✔ "New song by singer X"
✔ "New song by female singer Y"
✔ "New release by group Z"

⚡ Shortcode Conversion to HTML (NEW)

The plugin allows converting:

[mozbeats_ficha_tecnica]

into fixed HTML inside the post.

Benefits:

* Better SEO (content visible to Google)
* Higher performance (no dynamic processing)
* Independence from shortcode

✔ Automatic backup system
✔ Ability to restore posts
✔ Backup expires automatically after 24 hours

🧠 Smart Cache System

* Automatic cache of the technical sheet
* Automatic clearing when updating post

🖼️ Automatic Featured Image

* Automatically sets the image
* Only if the post has no image

🔎 Automatic SEO

* Automatic meta description generation
* Compatible with Yoast SEO
* Compatible with Rank Math
* Schema.org (MusicRecording)

⚙️ Dashboard

Manage everything directly from WordPress:

* Enable/disable cache
* Control automatic SEO
* Manage music genres
* Convert shortcodes in bulk
* Restore posts

== How It Works ==

Example title:

Mr Bow & Liloca - Teu Amor (feat. Cleyton David & Tamyris Moiane)

The plugin automatically:

Creates tags:

* Mr Bow
* Liloca
* Cleyton David
* Tamyris Moiane
* 2026

And generates:

* Complete technical sheet
* SEO-optimized text
* Music genre detection
* Artist type identification

== Shortcode ==

Use inside the post:

[mozbeats_ficha_tecnica]

[gerar_fica_tecnica]

== Installation ==

1. Upload the plugin to /wp-content/plugins/
2. Activate it in the WordPress dashboard
3. Configure it in the "Generate Sheet" menu
4. Use the shortcode in your posts

== Changelog ==

= 1.3.0 =

* New artist classification system (Male, Female, Group)
* Conversion of shortcode into permanent HTML
* Automatic backup system with 24h expiration
* Option to restore converted posts
* Improvements to artist management interface
* Performance improvements

= 1.2.0 =

* Complete Auto Tags system
* Automatic year tag
* Advanced grammar correction
* Dashboard management
* Cache improvements

= 1.1.0 =

* Automatic meta description
* Automatic featured image
* Automatic cache clearing

= 1.0.0 =

* Initial release
* Basic technical sheet generation

== Future Roadmap ==

* Automatic artist pages
* Custom Post Type for artists
* Download statistics
* Integrated music player
* Public API

== Author ==

Mozbeats
https://www.mozbeats.co.mz/

Generate Music Technical Sheet turns creating music posts into an automatic, professional, and scalable process.
