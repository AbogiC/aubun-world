-- Add PayPal order ID storage so pending orders can be refreshed from PayPal

ALTER TABLE orders
ADD COLUMN paypal_order_id VARCHAR(32) NULL AFTER status;

ALTER TABLE orders
ADD INDEX idx_orders_paypal_order_id (paypal_order_id);
