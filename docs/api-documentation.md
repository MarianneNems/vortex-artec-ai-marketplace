# VORTEX AI Marketplace - API Documentation

**Version:** 2.0  
**Last Updated:** December 2024

## Overview

The VORTEX AI Marketplace API provides RESTful endpoints for integrating with the platform's AI capabilities, blockchain functionality, and marketplace features. This documentation covers all public API endpoints available to developers and third-party integrations.

## Base URL

```
https://yoursite.com/wp-json/vortex/v1/
```

## Authentication

All API requests require authentication using one of the following methods:

### API Key Authentication

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/endpoint
```

### OAuth 2.0 Authentication

1. Register your application to receive client credentials
2. Obtain an access token using the OAuth 2.0 flow
3. Include the access token in the Authorization header

```bash
curl -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
     https://yoursite.com/wp-json/vortex/v1/endpoint
```

## Rate Limiting

API requests are subject to rate limiting based on your subscription tier:

- **Free Tier**: 100 requests per hour
- **Pro Tier**: 1,000 requests per hour
- **Enterprise Tier**: 10,000 requests per hour

Rate limit headers are included in all responses:
- `X-RateLimit-Limit`: Your rate limit ceiling
- `X-RateLimit-Remaining`: Number of requests remaining
- `X-RateLimit-Reset`: UTC timestamp when the rate limit resets

## Error Handling

The API uses standard HTTP status codes and returns error details in JSON format:

```json
{
  "code": "error_code",
  "message": "Human readable error message",
  "data": {
    "status": 400,
    "details": "Additional error details"
  }
}
```

### Common Error Codes

- `400` - Bad Request: Invalid request parameters
- `401` - Unauthorized: Invalid or missing authentication
- `403` - Forbidden: Insufficient permissions
- `404` - Not Found: Requested resource not found
- `429` - Too Many Requests: Rate limit exceeded
- `500` - Internal Server Error: Server-side error

## AI Art Generation API

### Generate Artwork

Generate AI-powered artwork using text prompts and customizable parameters.

```http
POST /ai/generate
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `prompt` | string | Yes | Text description for artwork generation |
| `format` | string | No | Output format (png, jpg, webp, mp4, obj, etc.) |
| `width` | integer | No | Output width in pixels (default: 512) |
| `height` | integer | No | Output height in pixels (default: 512) |
| `seed` | integer | No | Random seed for reproducible results |
| `model` | string | No | AI model to use for generation |
| `quality` | string | No | Quality level (draft, standard, premium) |
| `style` | string | No | Art style preference |

#### Example Request

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "A serene landscape with mountains and a lake",
    "format": "png",
    "width": 1024,
    "height": 768,
    "quality": "premium"
  }' \
  https://yoursite.com/wp-json/vortex/v1/ai/generate
```

#### Response

```json
{
  "success": true,
  "data": {
    "artwork_id": 12345,
    "url": "https://cdn.example.com/artwork/12345.png",
    "prompt": "A serene landscape with mountains and a lake",
    "format": "png",
    "width": 1024,
    "height": 768,
    "generation_time": 15.4,
    "metadata": {
      "seed": 42,
      "model": "standard",
      "quality": "premium"
    }
  }
}
```

### Analyze Artwork

Analyze existing artwork to extract metadata, style information, and quality metrics.

```http
POST /ai/analyze
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `artwork_id` | integer | No | ID of existing artwork to analyze |
| `file` | file | No | Upload file for analysis |
| `components` | array | No | Specific analysis components to run |

#### Example Request

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -F "file=@artwork.jpg" \
  https://yoursite.com/wp-json/vortex/v1/ai/analyze
```

#### Response

```json
{
  "success": true,
  "data": {
    "analysis_id": 67890,
    "artwork_id": 12345,
    "components": {
      "color_harmony": 8.5,
      "composition": 9.2,
      "depth_perspective": 7.8,
      "texture_quality": 8.9,
      "emotional_impact": 9.1
    },
    "style_tags": ["landscape", "naturalistic", "serene"],
    "quality_score": 8.7,
    "metadata": {
      "dimensions": "1024x768",
      "format": "jpg",
      "file_size": "2.3MB"
    }
  }
}
```

## Curation & Recommendations API

### Get Personalized Recommendations

Retrieve AI-powered artwork recommendations based on user preferences and behavior.

```http
GET /recommendations/{user_id}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | integer | Yes | User ID for recommendations |
| `type` | string | No | Type of recommendations (artwork, artist, style) |
| `limit` | integer | No | Number of recommendations (default: 10, max: 50) |
| `category` | string | No | Filter by artwork category |
| `price_range` | string | No | Price range filter (e.g., "100-500") |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/recommendations/123?type=artwork&limit=20
```

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "recommendations": [
      {
        "artwork_id": 456,
        "title": "Mountain Sunrise",
        "artist": "Jane Doe",
        "price": 250.00,
        "currency": "TOLA",
        "recommendation_score": 9.2,
        "reasons": ["similar style", "price range", "trending"]
      }
    ],
    "total": 20,
    "generated_at": "2024-12-01T10:00:00Z"
  }
}
```

### Get Market Trends

Analyze current market trends and emerging patterns in the art marketplace.

```http
GET /trends
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `category` | string | No | Specific category to analyze |
| `timeframe` | string | No | Time period (day, week, month, year) |
| `limit` | integer | No | Number of trends to return (default: 10) |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/trends?category=landscape&timeframe=month
```

#### Response

```json
{
  "success": true,
  "data": {
    "trends": [
      {
        "trend_id": 1,
        "category": "landscape",
        "trend_name": "Minimalist Nature",
        "growth_rate": 25.5,
        "avg_price": 320.00,
        "popular_tags": ["minimal", "nature", "peaceful"]
      }
    ],
    "timeframe": "month",
    "generated_at": "2024-12-01T10:00:00Z"
  }
}
```

## Marketplace API

### List Artwork

Retrieve artwork listings from the marketplace with filtering and pagination.

```http
GET /marketplace/artwork
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: 1) |
| `per_page` | integer | No | Items per page (default: 20, max: 100) |
| `category` | string | No | Filter by artwork category |
| `artist_id` | integer | No | Filter by specific artist |
| `format` | string | No | Filter by file format |
| `price_min` | number | No | Minimum price filter |
| `price_max` | number | No | Maximum price filter |
| `sort` | string | No | Sort order (price, date, popularity) |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/marketplace/artwork?category=landscape&per_page=50&sort=price
```

#### Response

```json
{
  "success": true,
  "data": {
    "artwork": [
      {
        "id": 123,
        "title": "Sunset Valley",
        "artist": {
          "id": 456,
          "name": "John Smith",
          "verified": true
        },
        "price": 150.00,
        "currency": "TOLA",
        "format": "jpg",
        "dimensions": "1920x1080",
        "created_at": "2024-11-15T14:30:00Z",
        "tags": ["landscape", "sunset", "nature"]
      }
    ],
    "pagination": {
      "page": 1,
      "per_page": 50,
      "total": 1250,
      "total_pages": 25
    }
  }
}
```

### Create Listing

Create a new marketplace listing for artwork.

```http
POST /marketplace/listing
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `artwork_id` | integer | Yes | ID of artwork to list |
| `price` | number | Yes | Listing price |
| `currency` | string | No | Currency (default: TOLA) |
| `royalty` | number | No | Artist royalty percentage (0-15%) |
| `description` | string | No | Listing description |
| `tags` | array | No | Artwork tags |

#### Example Request

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "artwork_id": 123,
    "price": 250.00,
    "currency": "TOLA",
    "royalty": 10,
    "description": "Beautiful landscape artwork",
    "tags": ["landscape", "nature", "serene"]
  }' \
  https://yoursite.com/wp-json/vortex/v1/marketplace/listing
```

#### Response

```json
{
  "success": true,
  "data": {
    "listing_id": 789,
    "artwork_id": 123,
    "price": 250.00,
    "currency": "TOLA",
    "status": "active",
    "created_at": "2024-12-01T10:00:00Z",
    "expires_at": "2024-12-31T23:59:59Z"
  }
}
```

## Blockchain API

### Get Wallet Balance

Check the balance of a specific wallet address.

```http
GET /blockchain/balance/{wallet_address}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `wallet_address` | string | Yes | Wallet address to check |
| `token` | string | No | Token type (default: TOLA) |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/blockchain/balance/1A2B3C4D5E6F7G8H9I0J
```

#### Response

```json
{
  "success": true,
  "data": {
    "wallet_address": "1A2B3C4D5E6F7G8H9I0J",
    "balances": [
      {
        "token": "TOLA",
        "balance": 1250.500000,
        "usd_value": 125.05
      }
    ],
    "last_updated": "2024-12-01T10:00:00Z"
  }
}
```

### Create Transaction

Initiate a blockchain transaction.

```http
POST /blockchain/transaction
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `from_wallet` | string | Yes | Sender wallet address |
| `to_wallet` | string | Yes | Recipient wallet address |
| `amount` | number | Yes | Transaction amount |
| `token` | string | No | Token type (default: TOLA) |
| `memo` | string | No | Transaction memo |

#### Example Request

```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "from_wallet": "1A2B3C4D5E6F7G8H9I0J",
    "to_wallet": "2B3C4D5E6F7G8H9I0J1K",
    "amount": 100.00,
    "token": "TOLA",
    "memo": "Artwork purchase"
  }' \
  https://yoursite.com/wp-json/vortex/v1/blockchain/transaction
```

#### Response

```json
{
  "success": true,
  "data": {
    "transaction_id": "tx_abc123def456",
    "from_wallet": "1A2B3C4D5E6F7G8H9I0J",
    "to_wallet": "2B3C4D5E6F7G8H9I0J1K",
    "amount": 100.00,
    "token": "TOLA",
    "status": "pending",
    "created_at": "2024-12-01T10:00:00Z",
    "blockchain_hash": "0x123abc456def789..."
  }
}
```

## User Management API

### Get User Profile

Retrieve user profile information.

```http
GET /users/{user_id}
```

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/users/123
```

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "username": "artist_jane",
    "display_name": "Jane Doe",
    "role": "artist",
    "verified": true,
    "member_since": "2024-01-15T09:30:00Z",
    "stats": {
      "artworks_created": 45,
      "artworks_sold": 12,
      "total_earnings": 2500.00,
      "followers": 156
    }
  }
}
```

### Update User Profile

Update user profile information.

```http
PUT /users/{user_id}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `display_name` | string | No | User's display name |
| `bio` | string | No | User biography |
| `avatar` | file | No | Profile avatar image |
| `social_links` | object | No | Social media links |

## Business Intelligence API

### Get Analytics

Retrieve analytics data for artists and administrators.

```http
GET /analytics/{user_id}
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | integer | Yes | User ID for analytics |
| `timeframe` | string | No | Time period (day, week, month, year) |
| `metrics` | array | No | Specific metrics to retrieve |

#### Example Request

```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://yoursite.com/wp-json/vortex/v1/analytics/123?timeframe=month
```

#### Response

```json
{
  "success": true,
  "data": {
    "user_id": 123,
    "timeframe": "month",
    "metrics": {
      "artwork_views": 1250,
      "artwork_likes": 89,
      "artwork_sales": 8,
      "revenue": 800.00,
      "follower_growth": 15
    },
    "generated_at": "2024-12-01T10:00:00Z"
  }
}
```

## Webhooks

The API supports webhooks for real-time notifications of platform events.

### Register Webhook

```http
POST /webhooks/register
```

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `event` | string | Yes | Event type to listen for |
| `url` | string | Yes | Webhook endpoint URL |
| `secret` | string | Yes | Secret for webhook verification |

#### Available Events

- `artwork.created` - New artwork uploaded
- `artwork.sold` - Artwork purchase completed
- `nft.minted` - NFT minted successfully
- `transaction.completed` - Blockchain transaction confirmed
- `user.milestone` - User achievement unlocked

## SDKs and Libraries

Official SDKs are available for popular programming languages:

- **JavaScript/Node.js**: `npm install vortex-api-client`
- **Python**: `pip install vortex-api-client`
- **PHP**: `composer require vortex/api-client`
- **Java**: Maven and Gradle packages available

## Support

For API support and questions:

- **Documentation**: https://docs.vortexartec.com
- **Email**: api-support@vortexartec.com
- **Discord**: [Join our developer community](https://discord.gg/vortex-dev)
- **GitHub**: [Report issues](https://github.com/vortex-ai/api-issues)

## Changelog

### Version 2.0 (December 2024)
- Complete API redesign with improved performance
- New AI generation endpoints
- Enhanced authentication system
- Improved error handling and documentation

### Version 1.5 (November 2024)
- Added blockchain API endpoints
- Webhook support
- Rate limiting implementation
- SDK releases

---

*For the most up-to-date API documentation, visit our [developer portal](https://developers.vortexartec.com)* 