# Lazy Load Placeholders plugin for Craft CMS 3.x

Create blurry images from original images + lazy load functionality

#### Admin Setup
- Check assets folder in ```Settings > Lazy Load Placeholders```
- Click ```Generate Placehlders Now``` if you want generate plceholders 

#### Template Setup
- For get placeholder image add ```LLPlaceholder``` filter to asset Ex: ```{{ entry.image.one()|LLPlaceholder }}```
- If you want get base64 url add ```LLPlaceholder(true)``` filter to asset Ex: ```{{ entry.image.one()|LLPlaceholder(true) }}```

#### Lazy Load Setup
1. Lazy Load Images
    - Add class ```lazy-image```
    - Add placeholder asset to ```src``` ``` {{ entry.image.one()|LLPlaceholder }} ``` or ``` {{ entry.image.one()|LLPlaceholder(true) }} ```
    - Add normal asset url to ```image-src``` ```{{ entry.image.one().getUrl() }}```
    - EX: ```<img image-src="{{ entry.image.one().getUrl() }}" src="{{ entry.image.one()|LLPlaceholder }}" class="lazy-image">```

2. Lazy Load Background 
    - Add class ```lazy-background```
    - Add placeholder asset to ```style="background-image:url({{ entry.image.one()|LLPlaceholder }})" ``` or ``` {{ entry.image.one()|LLPlaceholder(true) }} ```
    - Add normal asset url to ```background-src="{{ entry.image.one().getUrl() }}"```
    - EX: ```<div class="lazy-background" background-src="{{ entry.image.one().getUrl() }}" style="background-image: url({{ entry.image.one()|LLPlaceholder }});"></div>```

3. Lazy Load Scripts
    - Add ```{{ loadLazyLoadScripts() }}``` in footer, for load lazy load scripts
