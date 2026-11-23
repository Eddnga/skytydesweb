<?php
declare(strict_types=1);

/** Starter content so the site is not empty on day one. Everything is editable in the dashboard. */
function seed_settings(): void
{
    $s = [
        'site_name' => 'SkyTydes Safaris',
        'tagline' => 'Follow the Tides • Feel the Wild',
        'whatsapp' => '254723022702',
        'phone1' => '+254 723 022 702',
        'phone2' => '+254 713 716 986',
        'email' => 'info@skytydessafaris.com',
        'address' => 'Mobil Plaza, Muthaiga, Nairobi, Kenya',
        'postal' => 'P.O. Box 64364 – 00620',
        'hours' => 'Mon – Sat, 8:00 am – 6:00 pm',
        'meta_description' => 'SkyTydes Safaris plans tailor-made Kenya safaris and coast holidays — the Maasai Mara, Amboseli, the Aberdares and the Indian Ocean. Plan your trip on WhatsApp.',
        'intro_title' => 'Safaris that actually leave the group chat.',
        'intro_body' => "We are a Nairobi-based safari company that plans every detail for you — transport, lodges, park fees and the experiences in between — so all you have to do is pack your bag and show up.\n\nWhether it is a weekend in the Mara with friends, a family trip under Kilimanjaro or a honeymoon on the coast, we shape the journey around you.",
        'about_title' => 'About SkyTydes Safaris',
        'about_body' => "SkyTydes Safaris is a Kenyan safari company based in Muthaiga, Nairobi, led by directors **Samuel Mwangi** and **Susan Muthoni**.\n\nWe believe the best trips are the ones that actually happen. That is why we take care of the planning — routes, stays, park fees, transfers and timing — and keep you updated on WhatsApp from the first message to the drive home.\n\n## What guides us\n- Honest, upfront pricing\n- Safe, comfortable vehicles and experienced guides\n- Respect for wildlife, parks and local communities\n- Trips designed around your budget and your people",
        'why_us' => "Everything planned|Transport, lodges, park fees and timing handled for you.\nPlanned on WhatsApp|Chat with a real person — quick answers, no forms.\nBuilt around your budget|Group, family and couples packages that fit.\nLocal and trusted|A Nairobi team that knows Kenya's parks and coast.",
        'cta_title' => 'Ready to make it happen?',
        'cta_body' => 'Tell us where, when and who is coming. We will send you a plan on WhatsApp.',
        'social_instagram' => '',
        'social_facebook' => '',
        'social_tiktok' => '',
        'review_notice' => 'Thank you! Your review has been received and will appear once it has been approved.',
    ];
    foreach ($s as $k => $v) set_setting($k, $v);
}

function seed_content(): void
{
    $now = now();
    $hero = [
        ['Follow the Tides. Feel the Wild.', 'Tailor-made Kenya safaris — planned for you, from the first message to the last sunset.', 'SkyTydes Safaris', '1.4061° S, 35.1426° E · Maasai Mara', 'seed/mara.jpg', 'Explore tours', 'tours.php'],
        ['Beneath Kilimanjaro.', 'Walk with the giants of Amboseli at golden hour.', 'Amboseli National Park', '2.6527° S, 37.2606° E · Amboseli', 'seed/amboseli.jpg', 'See Amboseli', 'destination.php?slug=amboseli'],
        ['From savannah to sea.', 'End your safari with white sand and the warm Indian Ocean.', 'The Kenyan Coast', '4.2767° S, 39.5950° E · The Coast', 'seed/coast.jpg', 'Plan a coast trip', 'destination.php?slug=the-coast'],
    ];
    foreach ($hero as $i => $h) insert('hero_slides', ['title' => $h[0], 'subtitle' => $h[1], 'eyebrow' => $h[2], 'caption' => $h[3], 'image' => $h[4], 'cta_label' => $h[5], 'cta_link' => $h[6], 'sort' => $i, 'active' => 1]);

    $dest = [
        ['Maasai Mara', 'Narok County', 'Home of the Great Migration', 'seed/mara.jpg', '1.4061° S, 35.1426° E', 'July – October for the migration; great game viewing all year',
            'Endless golden plains, big cats and the world-famous wildebeest migration.',
            "The Maasai Mara is Kenya's most celebrated reserve — rolling grasslands dotted with acacia trees, home to lions, cheetahs, leopards, elephants and buffalo.\n\nBetween July and October, hundreds of thousands of wildebeest and zebra cross from the Serengeti, braving the Mara River in one of nature's greatest spectacles.\n\n## Highlights\n- Big cat sightings on morning and evening game drives\n- The Great Migration and Mara River crossings (seasonal)\n- Hot-air balloon safaris at sunrise (optional)\n- Visits to Maasai communities"],
        ['Amboseli', 'Kajiado County', 'Beneath Kilimanjaro', 'seed/amboseli.jpg', '2.6527° S, 37.2606° E', 'June – October and January – February for clear mountain views',
            'Big-tusked elephants against the snow-capped peak of Mount Kilimanjaro.',
            "Amboseli is famous for its large elephant herds and postcard views of Mount Kilimanjaro across the border in Tanzania.\n\nSwamps fed by the mountain's snowmelt draw wildlife all year, making it one of the easiest parks to see elephants up close.\n\n## Highlights\n- Elephant herds with Kilimanjaro as the backdrop\n- Observation Hill views over the swamps\n- Excellent birdlife\n- Easy reach from Nairobi"],
        ['The Aberdares', 'Nyeri County', 'Highland forests & The Ark', 'seed/ark.jpg', '0.3500° S, 36.7333° E', 'All year; drier months are June – September and December – March',
            'Misty mountain forests, waterfalls and night-time game viewing at The Ark.',
            "The Aberdare range offers a cooler, greener safari — bamboo forests, moorland, waterfalls and rare animals like the bongo and giant forest hog.\n\nStay at a tree lodge such as The Ark, where a floodlit waterhole lets you watch elephants and buffalo from the lodge after dark.\n\n## Highlights\n- Overnight at a tree lodge with a floodlit waterhole\n- Waterfalls and forest scenery\n- A perfect short trip from Nairobi"],
        ['The Coast', 'Indian Ocean', 'White sands & warm water', 'seed/coast.jpg', '4.2767° S, 39.5950° E', 'All year; calmest seas December – March',
            'Powder-white beaches and turquoise water — the perfect end to a safari.',
            "Kenya's coastline mixes white-sand beaches, coral reefs and Swahili culture.\n\nRelax at a beach resort, snorkel the reefs, explore old towns and enjoy fresh seafood — on its own or as a 'bush and beach' finish to your safari.\n\n## Highlights\n- Beach resorts and private villas\n- Snorkelling and dhow trips\n- Swahili culture and cuisine"],
    ];
    $ids = [];
    foreach ($dest as $i => $d) {
        $ids[$d[0]] = insert('destinations', ['name' => $d[0], 'slug' => slugify($d[0] === 'The Coast' ? 'the-coast' : $d[0]), 'region' => $d[1], 'tagline' => $d[2], 'image' => $d[3], 'coords' => $d[4], 'best_time' => $d[5], 'summary' => $d[6], 'body' => $d[7], 'sort' => $i, 'active' => 1]);
    }

    $tours = [
        ['3-Day Maasai Mara Safari', 'Maasai Mara', 3, 'Group & private', 'seed/mara.jpg', 1,
            'Three days of game drives in Kenya\'s most famous reserve — big cats, vast plains and, in season, the Great Migration.',
            "A classic Mara safari from Nairobi. Travel through the Great Rift Valley, spend two full days on the plains and return with memories (and photos) to last a lifetime.",
            "Morning and evening game drives\nRift Valley viewpoint stop\nBig Five country\nOptional sunrise balloon safari",
            "Transport in a safari van or Land Cruiser with pop-up roof\nProfessional driver-guide\nAccommodation as listed\nMeals as listed\nPark entry fees\nDrinking water on game drives",
            "Flights and visas\nTips and personal expenses\nOptional balloon safari and village visit\nDrinks at the lodge",
            [['Nairobi to the Maasai Mara', 'Early pick-up in Nairobi and drive via the Great Rift Valley viewpoint. Arrive in time for lunch, then head out on an afternoon game drive.', 'Lunch, Dinner', 'Camp or lodge in the Mara'],
             ['Full day in the Mara', 'A full day exploring the reserve with a picnic lunch — looking for lions, cheetahs, elephants and, in season, river crossings.', 'Breakfast, Lunch, Dinner', 'Camp or lodge in the Mara'],
             ['Morning drive & return', 'An early game drive, breakfast, then the drive back to Nairobi, arriving in the evening.', 'Breakfast, Lunch', '—']]],
        ['2-Day Amboseli Safari', 'Amboseli', 2, 'Group & private', 'seed/amboseli.jpg', 1,
            'Elephants, open plains and the best views of Mount Kilimanjaro — an easy escape from Nairobi.',
            "Perfect for a weekend: drive south to Amboseli, spend the afternoon and next morning on game drives, and watch the sun set behind Kilimanjaro.",
            "Elephant herds up close\nKilimanjaro views at sunrise and sunset\nObservation Hill",
            "Transport with pop-up roof\nDriver-guide\nAccommodation\nMeals as listed\nPark entry fees",
            "Tips and personal expenses\nDrinks",
            [['Nairobi to Amboseli', 'Morning departure from Nairobi, arriving for lunch. Afternoon game drive as the mountain comes into view.', 'Lunch, Dinner', 'Lodge or camp near Amboseli'],
             ['Sunrise drive & return', 'Early drive for the clearest views of Kilimanjaro, breakfast, and return to Nairobi.', 'Breakfast, Lunch', '—']]],
        ['Aberdares & The Ark Overnight', 'The Aberdares', 2, 'Short break', 'seed/ark.jpg', 1,
            'A cool highland escape with a night at a tree lodge overlooking a floodlit waterhole.',
            "Swap the city for misty forests. Spend the night at The Ark and watch elephants, buffalo and more come to the waterhole after dark.",
            "Night game viewing from the lodge\nForest and waterfall scenery\nGreat for families",
            "Transport\nDriver-guide\nOne night's accommodation\nMeals as listed\nPark entry fees",
            "Tips and personal expenses\nDrinks",
            [['Nairobi to the Aberdares', 'Drive north to the Aberdare Country Club for lunch, then transfer to The Ark with a game drive on the way.', 'Lunch, Dinner', 'The Ark'],
             ['Return to Nairobi', 'Breakfast after a night of wildlife viewing, then the drive back to Nairobi.', 'Breakfast', '—']]],
        ['6-Day Bush & Beach', 'The Coast', 6, 'Holiday', 'seed/coast.jpg', 0,
            'Safari thrills followed by days of sun, sand and the Indian Ocean.',
            "Combine game drives in the bush with relaxing days at the coast — the perfect Kenyan holiday.",
            "Game drives\nBeach resort stay\nOptional snorkelling and dhow trip",
            "Ground transport\nAccommodation\nMeals as listed\nPark entry fees",
            "Flights between safari and coast (if chosen)\nTips and personal expenses",
            [['Arrival & safari', 'Head out to the park for your first game drive.', 'Lunch, Dinner', 'Safari camp'],
             ['Full safari day', 'A full day of game viewing.', 'Breakfast, Lunch, Dinner', 'Safari camp'],
             ['Transfer to the coast', 'Travel to the coast and check in to your beach resort.', 'Breakfast, Dinner', 'Beach resort'],
             ['Beach day', 'Relax, swim or join an optional excursion.', 'Breakfast, Dinner', 'Beach resort'],
             ['Beach day', 'Snorkelling, dhow trip or simply rest.', 'Breakfast, Dinner', 'Beach resort'],
             ['Departure', 'Breakfast and transfer for your journey home.', 'Breakfast', '—']]],
    ];
    foreach ($tours as $i => $t) {
        $tid = insert('tours', ['title' => $t[0], 'slug' => slugify($t[0]), 'destination_id' => $ids[$t[1]], 'duration_days' => $t[2], 'tour_type' => $t[3], 'image' => $t[4], 'featured' => $t[5], 'summary' => $t[6], 'body' => $t[7], 'highlights' => $t[8], 'includes' => $t[9], 'excludes' => $t[10], 'price_from' => null, 'currency' => 'KES', 'price_note' => 'per person sharing', 'sort' => $i, 'active' => 1, 'created_at' => $now]);
        foreach ($t[11] as $d => $day) insert('itinerary_days', ['tour_id' => $tid, 'day_no' => $d + 1, 'title' => $day[0], 'body' => $day[1], 'meals' => $day[2], 'stay' => $day[3]]);
    }

    $services = [
        ['Tailor-made safaris', 'compass', 'Trips built around your dates, budget and the people coming with you.'],
        ['Group & corporate trips', 'users', 'Friends, chamas, teams and retreats — we handle the logistics for everyone.'],
        ['Hotel & lodge bookings', 'bed', 'Camps, lodges and beach resorts to suit every style and budget.'],
        ['Airport & road transfers', 'car', 'Reliable transfers in comfortable safari vehicles with experienced drivers.'],
        ['Beach holidays', 'sun', 'Coast escapes on their own or as the perfect end to your safari.'],
        ['Park fees & permits', 'ticket', 'We sort out park entry and permits so there are no surprises at the gate.'],
    ];
    foreach ($services as $i => $s) insert('services', ['title' => $s[0], 'icon' => $s[1], 'summary' => $s[2], 'sort' => $i, 'active' => 1]);

    $faqs = [
        ['How do I book a trip?', 'Tap any "Book on WhatsApp" button and send us the message — it already includes the trip you picked. We will reply with availability, a final price and the next steps.', 'Booking'],
        ['Do I need to pay a deposit?', 'Yes, a deposit secures your booking. We will share the amount and payment options on WhatsApp once your dates are confirmed.', 'Booking'],
        ['Can you plan a trip just for our group?', 'Absolutely. Every package can be made private, and we regularly plan trips for friends, families, chamas and companies.', 'Planning'],
        ['What is the best time to visit the Maasai Mara?', 'Game viewing is good all year. The Great Migration is usually in the Mara between July and October.', 'Planning'],
        ['What should I pack?', 'Comfortable neutral-coloured clothes, a warm layer for early mornings, a hat, sunscreen, sunglasses, binoculars and your camera.', 'Travel'],
        ['Are park fees included?', 'Park entry fees are included in our safari packages unless a package says otherwise. Citizen, resident and non-resident rates differ.', 'Pricing'],
    ];
    foreach ($faqs as $i => $f) insert('faqs', ['question' => $f[0], 'answer' => $f[1], 'category' => $f[2], 'sort' => $i, 'active' => 1]);

    insert('posts', ['title' => 'When is the best time to see the Great Migration?', 'slug' => 'best-time-great-migration', 'category' => 'Travel tips', 'author' => 'SkyTydes Safaris', 'image' => 'seed/mara.jpg', 'published' => 1, 'published_at' => $now, 'created_at' => $now,
        'excerpt' => 'A simple month-by-month guide to planning your Maasai Mara trip around the wildebeest migration.',
        'body' => "Every year, huge herds of wildebeest and zebra move between Tanzania's Serengeti and Kenya's Maasai Mara in search of fresh grass.\n\n## July to October: the Mara season\nThis is when the herds are usually in the Mara and when the dramatic Mara River crossings happen. It is the busiest time of year, so book early.\n\n## November to June\nThe herds head south, but the Mara is still excellent for big cats and resident wildlife — with fewer vehicles and lower lodge rates.\n\n## Our tip\nIf river crossings are your dream, aim for August or September and allow at least two full days in the reserve.\n\nReady to plan? Send us a message on WhatsApp and we will put together a trip for your dates."]);
    insert('posts', ['title' => 'What to pack for your first safari', 'slug' => 'what-to-pack-first-safari', 'category' => 'Travel tips', 'author' => 'SkyTydes Safaris', 'image' => 'seed/pack.jpg', 'published' => 1, 'published_at' => date('Y-m-d H:i:s', time() - 86400 * 5), 'created_at' => $now,
        'excerpt' => 'Light layers, neutral colours and a good pair of binoculars — our simple safari packing list.',
        'body' => "Packing for a safari is easier than you think.\n\n## Clothes\n- Neutral colours (khaki, beige, olive)\n- A warm fleece or jacket for early mornings\n- A wide-brim hat\n- Comfortable closed shoes\n\n## Essentials\n- Sunscreen and sunglasses\n- Binoculars\n- Camera with spare batteries\n- Any personal medication\n\nTravel light — soft bags fit best in safari vehicles."]);

    $gal = [['seed/mara.jpg', 'Wildebeest on the Mara plains', 'Wildlife'], ['seed/amboseli.jpg', 'Elephants beneath Kilimanjaro', 'Wildlife'], ['seed/ark.jpg', 'The Ark, Aberdares', 'Lodges'], ['seed/coast.jpg', 'Sunset on the coast', 'Beach'], ['seed/pack.jpg', 'Ready for the road', 'Adventure']];
    foreach ($gal as $i => $g) insert('gallery', ['image' => $g[0], 'caption' => $g[1], 'category' => $g[2], 'sort' => $i, 'active' => 1, 'created_at' => $now]);
}
