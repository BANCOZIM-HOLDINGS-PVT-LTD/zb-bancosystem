# Product Catalog Schema

This document outlines the database schema for the product catalog.

## Tables

### `product_categories`

Stores the main product categories.

| Column | Type | Description |
| --- | --- | --- |
| `id` | `bigint`, unsigned, auto-increment | Primary Key |
| `name` | `varchar(255)` | The name of the category (e.g., "Agriculture"). |
| `emoji` | `varchar(255)` | The emoji for the category (e.g., "🌾"). |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `product_sub_categories`

Stores the sub-categories for each main category.

| Column | Type | Description |
| --- | --- | --- |
| `id` | `bigint`, unsigned, auto-increment | Primary Key |
| `product_category_id` | `bigint`, unsigned | Foreign key to `product_categories.id`. |
| `name` | `varchar(255)` | The name of the sub-category (e.g., "Cash Crops"). |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `products`

Stores the individual products.

| Column | Type | Description |
| --- | --- | --- |
| `id` | `bigint`, unsigned, auto-increment | Primary Key |
| `product_sub_category_id` | `bigint`, unsigned | Foreign key to `product_sub_categories.id`. |
| `name` | `varchar(255)` | The name of the product (e.g., "Cotton"). |
| `base_price` | `decimal(10, 2)` | The base price of the product. |
| `image_url` | `varchar(255)` | URL for the product image. |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `product_package_sizes`

Stores the different package sizes for each product.

| Column | Type | Description |
| --- | --- | --- |
| `id` | `bigint`, unsigned, auto-increment | Primary Key |
| `product_id` | `bigint`, unsigned | Foreign key to `products.id`. |
| `name` | `varchar(255)` | The name of the package size (e.g., "1 Ha"). |
| `multiplier` | `decimal(10, 2)` | The multiplier for the base price. |
| `custom_price` | `decimal(10, 2)`, nullable | An optional custom price that overrides the formula-based price. |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |

### `repayment_terms`

Stores the available repayment terms.

| Column | Type | Description |
| --- | --- | --- |
| `id` | `bigint`, unsigned, auto-increment | Primary Key |
| `months` | `int` | The number of months for the repayment term (e.g., 6, 12, 18). |
| `interest_rate` | `decimal(5, 2)` | The interest rate for the term. |
| `created_at` | `timestamp` | |
| `updated_at` | `timestamp` | |
