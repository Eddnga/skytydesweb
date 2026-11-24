<?php
declare(strict_types=1);

/**
 * Content types managed by admin/content.php.
 * Field: [name, type, label, options]
 * types: text, textarea, md, lines, number, money, select, relation, image, bool, date, url
 */
function entities(): array
{
    $dests = fn() => array_column(rows('SELECT id, name FROM destinations ORDER BY sort, name'), 'name', 'id');
    return [
        'hero' => [
            'table' => 'hero_slides', 'label' => 'Hero slides', 'one' => 'slide', 'icon' => 'image', 'title' => 'title',
            'order' => 'sort ASC, id ASC',
            'help' => 'The large rotating images at the top of the home page. Use wide landscape photos (at least 1800px wide).',
            'list' => ['image' => 'Image', 'title' => 'Title', 'caption' => 'Caption', 'active' => 'Status'],
            'fields' => [
                ['image', 'image', 'Background image', ['required' => true]],
                ['eyebrow', 'text', 'Small label above the title', ['hint' => 'e.g. Amboseli National Park']],
                ['title', 'text', 'Title', ['required' => true]],
                ['subtitle', 'textarea', 'Subtitle'],
                ['caption', 'text', 'Location caption', ['hint' => 'Shown at the bottom, e.g. 1.4061° S, 35.1426° E · Maasai Mara']],
                ['cta_label', 'text', 'Button text', ['hint' => 'e.g. Explore tours']],
                ['cta_link', 'text', 'Button link', ['hint' => 'A page on this site, e.g. tours.php or tour.php?slug=3-day-maasai-mara-safari']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Show on website', ['default' => 1]],
            ],
        ],
        'tours' => [
            'table' => 'tours', 'label' => 'Tour packages', 'one' => 'tour', 'icon' => 'map', 'title' => 'title', 'slug' => true, 'timestamps' => true,
            'order' => 'sort ASC, id DESC', 'view' => 'tour.php?slug=',
            'list' => ['image' => 'Image', 'title' => 'Tour', 'destination_id' => 'Destination', 'price_from' => 'From', 'featured' => 'Featured', 'active' => 'Status'],
            'itinerary' => true,
            'fields' => [
                ['title', 'text', 'Tour name', ['required' => true]],
                ['destination_id', 'relation', 'Destination', ['options' => $dests]],
                ['duration_days', 'number', 'Duration (days)'],
                ['tour_type', 'text', 'Tour type', ['hint' => 'e.g. Group & private, Short break, Honeymoon']],
                ['price_from', 'money', 'Price from', ['hint' => 'Leave empty to show "Price on request"']],
                ['currency', 'select', 'Currency', ['options' => ['KES' => 'KES', 'USD' => 'USD'], 'default' => 'KES']],
                ['price_note', 'text', 'Price note', ['hint' => 'e.g. per person sharing']],
                ['summary', 'textarea', 'Short summary', ['hint' => 'One or two sentences for cards and search results.']],
                ['body', 'md', 'Overview'],
                ['highlights', 'lines', 'Highlights', ['hint' => 'One per line']],
                ['includes', 'lines', 'What\'s included', ['hint' => 'One per line']],
                ['excludes', 'lines', 'Not included', ['hint' => 'One per line']],
                ['image', 'image', 'Main image'],
                ['featured', 'bool', 'Feature on the home page'],
                ['seo_title', 'text', 'Google title (optional)', ['hint' => 'Up to 60 characters. Leave empty to use the name. e.g. 3-Day Maasai Mara Safari from Nairobi']],
                ['seo_description', 'textarea', 'Google description (optional)', ['hint' => 'Up to 160 characters, shown under the title on Google. Leave empty to use the summary.']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'destinations' => [
            'table' => 'destinations', 'label' => 'Destinations', 'one' => 'destination', 'icon' => 'pin', 'title' => 'name', 'slug' => true,
            'order' => 'sort ASC, name ASC', 'view' => 'destination.php?slug=',
            'list' => ['image' => 'Image', 'name' => 'Destination', 'region' => 'Region', 'active' => 'Status'],
            'fields' => [
                ['name', 'text', 'Name', ['required' => true]],
                ['region', 'text', 'Region / county'],
                ['tagline', 'text', 'Tagline', ['hint' => 'e.g. Home of the Great Migration']],
                ['summary', 'textarea', 'Short summary'],
                ['body', 'md', 'Full description'],
                ['best_time', 'text', 'Best time to visit'],
                ['coords', 'text', 'Coordinates', ['hint' => 'e.g. 1.4061° S, 35.1426° E']],
                ['image', 'image', 'Image'],
                ['seo_title', 'text', 'Google title (optional)', ['hint' => 'Up to 60 characters. Leave empty to use the name. e.g. 3-Day Maasai Mara Safari from Nairobi']],
                ['seo_description', 'textarea', 'Google description (optional)', ['hint' => 'Up to 160 characters, shown under the title on Google. Leave empty to use the summary.']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'posts' => [
            'table' => 'posts', 'label' => 'Blog posts', 'one' => 'post', 'icon' => 'pen', 'title' => 'title', 'slug' => true, 'timestamps_created' => true,
            'order' => 'published_at DESC, id DESC', 'view' => 'post.php?slug=',
            'list' => ['image' => 'Image', 'title' => 'Title', 'category' => 'Category', 'published_at' => 'Date', 'published' => 'Status'],
            'fields' => [
                ['title', 'text', 'Title', ['required' => true]],
                ['category', 'text', 'Category', ['hint' => 'e.g. Travel tips, Destinations, News']],
                ['excerpt', 'textarea', 'Excerpt', ['hint' => 'Short intro shown on the blog list.']],
                ['body', 'md', 'Article'],
                ['image', 'image', 'Cover image'],
                ['author', 'text', 'Author', ['default' => 'SkyTydes Safaris']],
                ['published_at', 'date', 'Publish date'],
                ['published', 'bool', 'Published', ['default' => 1]],
                ['seo_title', 'text', 'Google title (optional)', ['hint' => 'Up to 60 characters. Leave empty to use the name. e.g. 3-Day Maasai Mara Safari from Nairobi']],
                ['seo_description', 'textarea', 'Google description (optional)', ['hint' => 'Up to 160 characters, shown under the title on Google. Leave empty to use the summary.']],
            ],
        ],
        'services' => [
            'table' => 'services', 'label' => 'Services', 'one' => 'service', 'icon' => 'star', 'title' => 'title',
            'order' => 'sort ASC, id ASC',
            'list' => ['icon' => 'Icon', 'title' => 'Service', 'summary' => 'Summary', 'active' => 'Status'],
            'fields' => [
                ['title', 'text', 'Service name', ['required' => true]],
                ['icon', 'select', 'Icon', ['options' => ['compass' => 'Compass', 'users' => 'Group', 'bed' => 'Bed', 'car' => 'Vehicle', 'sun' => 'Sun', 'ticket' => 'Ticket', 'plane' => 'Plane', 'camera' => 'Camera', 'heart' => 'Heart', 'star' => 'Star']]],
                ['summary', 'textarea', 'Summary', ['required' => true]],
                ['body', 'md', 'More details (optional)'],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'faqs' => [
            'table' => 'faqs', 'label' => 'FAQs', 'one' => 'question', 'icon' => 'help', 'title' => 'question',
            'order' => 'sort ASC, id ASC',
            'list' => ['question' => 'Question', 'category' => 'Category', 'active' => 'Status'],
            'fields' => [
                ['question', 'text', 'Question', ['required' => true]],
                ['answer', 'md', 'Answer', ['required' => true]],
                ['category', 'text', 'Category', ['hint' => 'e.g. Booking, Planning, Pricing, Travel']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'offers' => [
            'table' => 'offers', 'label' => 'Special offers', 'one' => 'offer', 'icon' => 'tag', 'title' => 'title',
            'order' => 'sort ASC, id DESC',
            'help' => 'Offers show on the Offers page and the home page while they are published and not expired. Leave dates empty for an offer with no end date.',
            'list' => ['image' => 'Image', 'title' => 'Offer', 'tag' => 'Label', 'valid_until' => 'Ends', 'active' => 'Status'],
            'fields' => [
                ['title', 'text', 'Offer name', ['required' => true, 'hint' => 'e.g. Kids travel free']],
                ['tag', 'text', 'Highlight label', ['hint' => 'Short and bold, e.g. 25% off, Kids stay free, Free airport transfer']],
                ['summary', 'textarea', 'Short description', ['required' => true]],
                ['terms', 'lines', 'Terms & conditions', ['hint' => 'One condition per line']],
                ['tour_id', 'relation', 'Linked tour (optional)', ['options' => fn() => array_column(rows('SELECT id, title FROM tours ORDER BY title'), 'title', 'id')]],
                ['valid_from', 'date_opt', 'Starts', ['hint' => 'Leave empty to start now']],
                ['valid_until', 'date_opt', 'Ends', ['hint' => 'The offer hides itself after this date', 'end' => true]],
                ['image', 'image', 'Image'],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'team' => [
            'table' => 'team', 'label' => 'Your hosts', 'one' => 'person', 'icon' => 'users', 'title' => 'name',
            'order' => 'sort ASC, id ASC',
            'help' => 'The people behind SkyTydes, shown on the home and About pages. A friendly photo builds trust.',
            'list' => ['image' => 'Photo', 'name' => 'Name', 'role' => 'Role', 'active' => 'Status'],
            'fields' => [
                ['name', 'text', 'Name', ['required' => true]],
                ['role', 'text', 'Role', ['hint' => 'e.g. Director, Safari planner, Lead guide']],
                ['bio', 'textarea', 'Short bio'],
                ['image', 'image', 'Photo', ['hint' => 'A portrait photo works best']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Show on website', ['default' => 1]],
            ],
        ],
        'documents' => [
            'table' => 'documents', 'label' => 'Documents', 'one' => 'document', 'icon' => 'file', 'title' => 'title', 'timestamps_created' => true,
            'order' => 'featured DESC, sort ASC, id DESC',
            'help' => 'PDFs clients can read or download on the Brochures & documents page — company profile, rates, brochures, terms. Set rates you prefer to share personally to "On request".',
            'list' => ['image' => 'Cover', 'title' => 'Document', 'category' => 'Type', 'access' => 'Access', 'downloads' => 'Opened', 'active' => 'Status'],
            'fields' => [
                ['title', 'text', 'Title', ['required' => true, 'hint' => 'e.g. SkyTydes Company Profile 2027']],
                ['category', 'select', 'Type', ['options' => ['Company profile' => 'Company profile', 'Rates' => 'Rates', 'Brochure' => 'Brochure', 'Itinerary' => 'Itinerary', 'Terms & conditions' => 'Terms & conditions', 'Other' => 'Other'], 'default' => 'Brochure']],
                ['summary', 'textarea', 'Short description'],
                ['file', 'pdf', 'PDF file', ['required' => true]],
                ['access', 'select', 'Who can open it', ['options' => ['public' => 'Anyone — view & download', 'request' => 'On request — client asks on WhatsApp'], 'default' => 'public']],
                ['image', 'image', 'Cover image (optional)', ['hint' => 'Shown on the card. Use the first page of the PDF or a photo.']],
                ['featured', 'bool', 'Highlight (e.g. your main company profile)'],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Published', ['default' => 1]],
            ],
        ],
        'gallery' => [
            'table' => 'gallery', 'label' => 'Gallery', 'one' => 'photo', 'icon' => 'grid', 'title' => 'caption', 'timestamps_created' => true,
            'order' => 'sort ASC, id DESC', 'bulk_upload' => true,
            'list' => ['image' => 'Photo', 'caption' => 'Caption', 'category' => 'Category', 'active' => 'Status'],
            'fields' => [
                ['image', 'image', 'Photo', ['required' => true]],
                ['caption', 'text', 'Caption'],
                ['category', 'text', 'Category', ['hint' => 'e.g. Wildlife, Lodges, Beach, Guests']],
                ['sort', 'number', 'Display order', ['default' => 0]],
                ['active', 'bool', 'Show on website', ['default' => 1]],
            ],
        ],
    ] + array_combine(
        array_map(fn($k) => 'sec_' . $k, array_keys(custom_types())),
        array_map('section_entity', array_values(custom_types()))
    );
}
