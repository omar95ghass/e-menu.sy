# E-Menu API Endpoints Documentation

## Base URL
`http://localhost/e-menu/api`

## Authentication

### POST /auth/login
Login a restaurant owner or admin.

**Request Body:**
```json
{
  "email": "owner@restaurant.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "user": { ... },
    "restaurant": { ... },
    "subscription": { ... }
  }
}
```

### POST /auth/register
Register a new restaurant.

**Request Body:**
```json
{
  "email": "owner@restaurant.com",
  "password": "password123",
  "owner_name": "Owner Name",
  "restaurant_name": "Restaurant Name",
  "phone": "+963 11 123 4567",
  "city": "Damascus",
  "cuisine_type": "Syrian"
}
```

### POST /auth/logout
Logout the current user.

**Response:**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

### GET /auth/csrf-token
Get CSRF token for secure requests.

**Response:**
```json
{
  "ok": true,
  "token": "csrf_token_here"
}
```

## Restaurants

### GET /restaurants
Get all active restaurants.

**Query Parameters:**
- `search` - Search term
- `city` - Filter by city
- `cuisine_type` - Filter by cuisine type
- `page` - Page number
- `limit` - Items per page

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Restaurant Name",
      "slug": "restaurant-slug",
      "rating": 4.5,
      ...
    }
  ]
}
```

### GET /restaurants/{slug}
Get restaurant by slug.

**Response:**
```json
{
  "ok": true,
  "data": {
    "id": 1,
    "name": "Restaurant Name",
    ...
  }
}
```

### GET /restaurants/{slug}/menu
Get restaurant menu.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Category Name",
      "items": [...]
    }
  ]
}
```

### POST /restaurants/{slug}/review
Add review for a restaurant.

**Request Body:**
```json
{
  "user_name": "Customer Name",
  "rating": 5,
  "comment": "Great food!"
}
```

## Restaurant Dashboard (Authenticated)

### GET /restaurant/dashboard
Get current restaurant's dashboard data.

**Authentication:** Required (Restaurant Owner)

**Response:**
```json
{
  "ok": true,
  "data": {
    "id": 1,
    "name": "Restaurant Name",
    ...
  }
}
```

### PUT /restaurant/update
Update restaurant profile.

**Authentication:** Required

**Request Body:**
```json
{
  "name": "New Name",
  "description": "New description",
  "phone": "+963 11 123 4567",
  ...
}
```

### GET /restaurant/stats
Get restaurant statistics.

**Authentication:** Required

### POST /restaurant/upload-logo
Upload restaurant logo.

**Authentication:** Required

**Request:** multipart/form-data
- `logo` - Image file

### POST /restaurant/upload-cover
Upload restaurant cover image.

**Authentication:** Required

**Request:** multipart/form-data
- `cover` - Image file

## Menu Management (Authenticated)

### GET /menu/{restaurant_id}
Get restaurant menu.

**Response:**
```json
{
  "success": true,
  "data": [...]
}
```

### POST /menu/add-category
Add new category.

**Authentication:** Required

**Request Body:**
```json
{
  "name": "Category Name",
  "name_ar": "اسم الفئة",
  "sort_order": 0
}
```

### POST /menu/add-item
Add new menu item.

**Authentication:** Required

**Request Body:**
```json
{
  "category_id": 1,
  "name": "Item Name",
  "name_ar": "اسم الصنف",
  "price": 50000,
  "description": "Item description"
}
```

### PUT /menu/update-item
Update menu item.

**Authentication:** Required

**Request Body:**
```json
{
  "id": 1,
  "name": "Updated Name",
  "price": 55000,
  ...
}
```

### DELETE /menu/delete-item/{id}
Delete menu item.

**Authentication:** Required

### POST /menu/upload-image
Upload item image.

**Authentication:** Required

**Request:** multipart/form-data
- `image` - Image file
- `item_id` - Item ID

## Reviews

### POST /review/add
Add a new review.

**Request Body:**
```json
{
  "restaurant_id": 1,
  "menu_item_id": 1,
  "user_name": "Customer Name",
  "rating": 5,
  "comment": "Great food!"
}
```

### GET /review/{restaurant_id}
Get restaurant reviews.

**Query Parameters:**
- `rating` - Filter by rating
- `menu_item_id` - Filter by item
- `featured` - Get featured only
- `page` - Page number
- `limit` - Items per page

## Admin Endpoints

All admin endpoints require admin role.

### GET /admin/restaurants
Get all restaurants for admin management.

**Authentication:** Admin

### PUT /admin/activate/{restaurant_id}
Activate a restaurant.

**Authentication:** Admin

### DELETE /admin/remove/{restaurant_id}
Delete a restaurant.

**Authentication:** Admin

### POST /admin/add-plan
Create new subscription plan.

**Authentication:** Admin

**Request Body:**
```json
{
  "name": "Plan Name",
  "name_ar": "اسم الخطة",
  "price": 50000,
  "max_categories": 10,
  "max_items": 100,
  ...
}
```

### PUT /admin/edit-plan/{id}
Update subscription plan.

**Authentication:** Admin

### DELETE /admin/delete-plan/{id}
Delete subscription plan.

**Authentication:** Admin

## Analytics

### GET /analytics/restaurant
Get restaurant analytics.

**Authentication:** Required

**Query Parameters:**
- `start_date` - Start date
- `end_date` - End date

### GET /analytics/system
Get system-wide analytics.

**Authentication:** Admin

## Common Response Formats

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message here"
}
```

## Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

## Authentication

Most endpoints require authentication via session cookies. Include credentials in requests:

```javascript
fetch('/api/endpoint', {
  credentials: 'include'
});
```

## CSRF Protection

For POST/PUT/DELETE requests, include CSRF token:

```javascript
fetch('/api/endpoint', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfToken
  },
  credentials: 'include',
  body: JSON.stringify(data)
});
```
