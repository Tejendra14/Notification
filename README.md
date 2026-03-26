````markdown
# Notification System API

A simple notification system built with Laravel using Redis queue and rate limiting.

---

## Overview

This API allows users to send notifications with background processing using queues. It also includes rate limiting to control usage.

---

## Create Notification

POST `/api/v1/notifications`

### Request

```json
{
  "user_id": "ram",
  "type": "email",
  "channel": "notification",
  "payload": {
    "subject": "Math Test",
    "content": "Score: 85%",
    "recipient": "ram@test.com"
  }
}
````

### Response

```json
{
  "id": "unique-id",
  "status": "pending",
  "message": "Notification queued successfully",
  "remaining": 9
}
```

---

## Flow

Request -> Validate -> Save -> Queue -> Process -> Update status

* API receives request
* Checks rate limit (max 10 per hour)
* Saves notification as pending
* Pushes job to Redis queue
* Returns response immediately
* Worker processes job in background
* Status updates to sent or failed

---

## Rate Limiting

* Max 10 notifications per user per hour
* Exceeding limit returns 429 error
* Limit resets after 1 hour
* Response includes remaining count

---

## Retry

* Failed notifications are retried automatically
* 1st retry -> after 30 seconds
* 2nd retry -> after 2 minutes
* 3rd retry -> after 5 minutes

---

## Queue

* Improves API performance
* Handles multiple requests
* Runs tasks in background

---

## Redis

* Used for queue
* Used for rate limiting
* Used for caching

---

## Endpoints

| Method | Endpoint                                  | Description         |
| ------ | ----------------------------------------- | ------------------- |
| POST   | /api/v1/notifications                     | Create notification |
| GET    | /api/v1/notifications/rate-limit/{userId} | Check rate limit    |
| POST   | /api/v1/notifications/{id}/retry          | Retry failed        |
| GET    | /api/v1/monitoring/notifications          | List notifications  |
| GET    | /api/v1/monitoring/summary                | Summary             |

---

## Test

```bash
php artisan test:notification create --user=ram --score=85
```

---

## Setup

### Install

```bash
composer install
```

### Environment

```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Run

```bash
php artisan migrate
php artisan queue:work --queue=notifications
php artisan serve
```

---

## Summary

Request -> Save -> Queue -> Background processing -> Status update

```
```
