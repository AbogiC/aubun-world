INSERT INTO products (name, category, price, original_price, image, description, rating, reviews, sizes, colors, featured) VALUES
('Silk Evening Gown', 'Dresses', 1299.99, 1899.99, 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600', 'Elegant floor-length silk gown with delicate hand-stitched details.', 4.80, 124, '["XS","S","M","L","XL"]', '["Black","Ivory"]', 1),
('Cashmere Wool Coat', 'Outerwear', 1899.99, 2499.99, 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?w=600', 'Luxurious double-breasted coat in pure cashmere wool.', 4.90, 89, '["S","M","L","XL"]', '["Black","Charcoal"]', 1),
('Italian Leather Jacket', 'Outerwear', 2199.99, 2899.99, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600', 'Premium Italian leather jacket with silk lining.', 4.70, 156, '["XS","S","M","L","XL"]', '["Black"]', 1),
('Designer Trousers', 'Pants', 699.99, 899.99, 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600', 'Tailored high-waist trousers in premium wool blend.', 4.50, 67, '["XS","S","M","L"]', '["Black","White","Gray"]', 0),
('Pure Linen Shirt', 'Shirts', 399.99, 549.99, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600', 'Classic button-down shirt in premium Egyptian linen.', 4.60, 93, '["S","M","L","XL","XXL"]', '["White","Black"]', 0),
('Velvet Blazer', 'Blazers', 1599.99, 1999.99, 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=600', 'Luxurious velvet blazer with satin lapels.', 4.90, 45, '["XS","S","M","L"]', '["Black","Burgundy"]', 1),
('Silk Midi Skirt', 'Skirts', 599.99, 799.99, 'https://images.unsplash.com/photo-1583496661160-fb5886a0a1f0?w=600', 'Flowing midi skirt in pure mulberry silk.', 4.70, 78, '["XS","S","M","L","XL"]', '["Black","White"]', 0),
('Merino Wool Sweater', 'Knitwear', 499.99, 699.99, 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600', 'Luxuriously soft merino wool turtleneck sweater.', 4.60, 112, '["S","M","L","XL"]', '["Black","White","Gray"]', 0);

INSERT INTO guidelines (type, title, content, sort_order, is_active) VALUES
('size_guide', 'Tops & Shirts', JSON_OBJECT(
    'category', 'tops',
    'rows', JSON_ARRAY(
        JSON_OBJECT('size', 'XS', 'us', '0-2', 'uk', '4-6', 'eu', '32-34', 'bust', '31-32', 'waist', '23-24', 'hips', ''),
        JSON_OBJECT('size', 'S', 'us', '4-6', 'uk', '8-10', 'eu', '36-38', 'bust', '33-35', 'waist', '25-27', 'hips', ''),
        JSON_OBJECT('size', 'M', 'us', '8-10', 'uk', '12-14', 'eu', '40-42', 'bust', '36-38', 'waist', '28-30', 'hips', ''),
        JSON_OBJECT('size', 'L', 'us', '12-14', 'uk', '16-18', 'eu', '44-46', 'bust', '39-41', 'waist', '31-33', 'hips', ''),
        JSON_OBJECT('size', 'XL', 'us', '16-18', 'uk', '20-22', 'eu', '48-50', 'bust', '42-44', 'waist', '34-36', 'hips', ''),
        JSON_OBJECT('size', 'XXL', 'us', '20-22', 'uk', '24-26', 'eu', '52-54', 'bust', '45-47', 'waist', '37-39', 'hips', '')
    )
), 0, 1),
('size_guide', 'Bottoms', JSON_OBJECT(
    'category', 'bottoms',
    'rows', JSON_ARRAY(
        JSON_OBJECT('size', 'XS', 'us', '0-2', 'uk', '4-6', 'eu', '32-34', 'waist', '24-25', 'hips', '33-35'),
        JSON_OBJECT('size', 'S', 'us', '4-6', 'uk', '8-10', 'eu', '36-38', 'waist', '26-28', 'hips', '36-38'),
        JSON_OBJECT('size', 'M', 'us', '8-10', 'uk', '12-14', 'eu', '40-42', 'waist', '29-31', 'hips', '39-41'),
        JSON_OBJECT('size', 'L', 'us', '12-14', 'uk', '16-18', 'eu', '44-46', 'waist', '32-34', 'hips', '42-44'),
        JSON_OBJECT('size', 'XL', 'us', '16-18', 'uk', '20-22', 'eu', '48-50', 'waist', '35-37', 'hips', '45-47'),
        JSON_OBJECT('size', 'XXL', 'us', '20-22', 'uk', '24-26', 'eu', '52-54', 'waist', '38-40', 'hips', '48-50')
    )
), 1, 1),
('size_guide', 'Dresses', JSON_OBJECT(
    'category', 'dresses',
    'rows', JSON_ARRAY(
        JSON_OBJECT('size', 'XS', 'us', '0-2', 'uk', '4-6', 'eu', '32-34', 'bust', '31-32', 'waist', '23-24', 'hips', '33-35'),
        JSON_OBJECT('size', 'S', 'us', '4-6', 'uk', '8-10', 'eu', '36-38', 'bust', '33-35', 'waist', '25-27', 'hips', '36-38'),
        JSON_OBJECT('size', 'M', 'us', '8-10', 'uk', '12-14', 'eu', '40-42', 'bust', '36-38', 'waist', '28-30', 'hips', '39-41'),
        JSON_OBJECT('size', 'L', 'us', '12-14', 'uk', '16-18', 'eu', '44-46', 'bust', '39-41', 'waist', '31-33', 'hips', '42-44'),
        JSON_OBJECT('size', 'XL', 'us', '16-18', 'uk', '20-22', 'eu', '48-50', 'bust', '42-44', 'waist', '34-36', 'hips', '45-47'),
        JSON_OBJECT('size', 'XXL', 'us', '20-22', 'uk', '24-26', 'eu', '52-54', 'bust', '45-47', 'waist', '37-39', 'hips', '48-50')
    )
), 2, 1);

INSERT INTO guidelines (type, title, content, sort_order, is_active) VALUES
('fit_guide', 'Slim Fit', JSON_OBJECT(
    'type', 'fit_type',
    'fitId', 'slim',
    'name', 'Slim Fit',
    'icon', 'bi bi-person-standing',
    'description', 'Follows your body''s natural contours without being tight. Designed for a tailored, streamlined silhouette.',
    'badge', 'Tailored Shape',
    'badgeClass', 'bg-dark',
    'summary', 'Tailored, close to the body.'
), 0, 1),
('fit_guide', 'Regular Fit', JSON_OBJECT(
    'type', 'fit_type',
    'fitId', 'regular',
    'name', 'Regular Fit',
    'icon', 'bi bi-person-standing-dress',
    'description', 'A classic, comfortable cut that skims the body. Room to move while maintaining a polished appearance.',
    'badge', 'Most Popular',
    'badgeClass', 'bg-success',
    'summary', 'Classic cut, comfortable.'
), 1, 1),
('fit_guide', 'Relaxed Fit', JSON_OBJECT(
    'type', 'fit_type',
    'fitId', 'relaxed',
    'name', 'Relaxed Fit',
    'icon', 'bi bi-person-arms-up',
    'description', 'Generous cut with extra room through the body and sleeves. Perfect for casual, easy layering.',
    'badge', 'Casual Comfort',
    'badgeClass', 'bg-warning text-dark',
    'summary', 'Generous, easy layering.'
), 2, 1),
('fit_guide', 'Fit Note: Tops & Shirts', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Tops & Shirts',
    'note', 'Slim fit runs close to the body. If between sizes, we recommend sizing up for a more relaxed feel.'
), 3, 1),
('fit_guide', 'Fit Note: Bottoms', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Bottoms',
    'note', 'Our regular fit trousers sit at the natural waist. For a lower rise, consider sizing down.'
), 4, 1),
('fit_guide', 'Fit Note: Dresses', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Dresses',
    'note', 'Dresses are cut for a regular fit through the bodice. Belt loops allow you to adjust the waist.'
), 5, 1),
('fit_guide', 'Fit Note: Outerwear', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Outerwear',
    'note', 'Outerwear is designed to layer. Choose your regular size if wearing over a tee, size up for sweater layering.'
), 6, 1),
('fit_guide', 'Fit Note: Knits & Sweaters', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Knits & Sweaters',
    'note', 'Knits have natural stretch. Regular fit is intended as a relaxed, cozy silhouette.'
), 7, 1),
('fit_guide', 'Fit Note: Tailoring', JSON_OBJECT(
    'type', 'fit_note',
    'category', 'Tailoring',
    'note', 'Blazers and structured pieces are cut slim. We recommend trying your usual size first.'
), 8, 1);

INSERT INTO guidelines (type, title, content, sort_order, is_active) VALUES
('care_instruction', 'Machine Wash', JSON_OBJECT(
    'type', 'care_icon',
    'icon', 'bi bi-water',
    'label', 'Machine Wash',
    'detail', 'Cold water, gentle cycle'
), 0, 1),
('care_instruction', 'Dry Clean', JSON_OBJECT(
    'type', 'care_icon',
    'icon', 'bi bi-droplet-half',
    'label', 'Dry Clean',
    'detail', 'Recommended for structured pieces'
), 1, 1),
('care_instruction', 'Air Dry', JSON_OBJECT(
    'type', 'care_icon',
    'icon', 'bi bi-sun',
    'label', 'Air Dry',
    'detail', 'Lay flat, avoid direct sunlight'
), 2, 1),
('care_instruction', 'Iron Low', JSON_OBJECT(
    'type', 'care_icon',
    'icon', 'bi bi-thermometer-high',
    'label', 'Iron Low',
    'detail', 'Warm iron, no steam for silk'
), 3, 1),
('care_instruction', 'Cotton & Linen', JSON_OBJECT(
    'type', 'fabric_care',
    'name', 'Cotton & Linen',
    'care', 'Machine wash cold with like colors. Tumble dry low or line dry. Iron on medium heat. Do not bleach. Wash before wearing to minimize shrinkage.'
), 4, 1),
('care_instruction', 'Silk & Satin', JSON_OBJECT(
    'type', 'fabric_care',
    'name', 'Silk & Satin',
    'care', 'Dry clean only. If washing at home, hand wash in cold water with mild detergent. Do not wring. Hang or lay flat to dry away from direct sunlight. Iron on low heat inside out.'
), 5, 1),
('care_instruction', 'Wool & Cashmere', JSON_OBJECT(
    'type', 'fabric_care',
    'name', 'Wool & Cashmere',
    'care', 'Hand wash in cold water with wool-specific detergent. Gently squeeze — do not twist or wring. Lay flat on a towel to dry, reshaping while damp. Store folded, not hung.'
), 6, 1),
('care_instruction', 'Polyester & Blends', JSON_OBJECT(
    'type', 'fabric_care',
    'name', 'Polyester & Blends',
    'care', 'Machine wash warm with similar colors. Tumble dry low. Remove promptly to reduce wrinkles. Iron on low to medium heat if needed. Avoid fabric softener to maintain shape.'
), 7, 1),
('care_instruction', 'Denim', JSON_OBJECT(
    'type', 'fabric_care',
    'name', 'Denim',
    'care', 'Wash inside out in cold water to preserve color. Line dry or tumble dry low. Avoid frequent washing — spot clean when possible. Iron on medium heat if necessary.'
), 8, 1);

INSERT INTO guidelines (type, title, content, sort_order, is_active) VALUES
('product_guide', 'Universal Product Guide', JSON_OBJECT(
    'type', 'universal',
    'isUniversal', true,
    'productId', null,
    'fitNote', '',
    'notes', 'Our products are designed with care and attention to detail. Each piece is crafted to meet our quality standards. For specific product inquiries, refer to the individual product details or contact our support team.'
), 0, 1);