# Log Structure Documentation

## Overview

Light Logger uses a flexible schema system where each project defines its own custom fields. Logs consist of **core fields** (always required) and **user-defined fields** stored in the `data` object.

---

## Log Structure

```json
{
  "timestamp": "2025-11-25T10:30:45.123Z",
  "level": "info",
  "title": "Brief description of the event",
  "data": {
    // Your custom fields go here
    "user_id": "12345",
    "action": "login",
    "ip_address": "192.168.1.1"
  }
}
```

### Core Fields (Always Required)

| Field       | Type              | Description                                                        |
|-------------|-------------------|--------------------------------------------------------------------|
| `timestamp` | string (ISO 8601) | When the event occurred. Format: `YYYY-MM-DDTHH:mm:ss.sssZ`        |
| `level`     | string (enum)     | Log level. Values: `debug`, `info`, `warning`, `error`, `critical` |
| `title`     | string            | Short description of the event                                     |
| `data`      | object            | User-defined fields (defined in project schema)                    |

---

## Project Schema Definition

Before sending logs, you must define your project's schema through the API.

### Schema Structure

```json
{
  "schema": {
    "fields": [
      {
        "name": "user_id",
        "type": "string",
        "indexed": true,
        "required": false,
        "description": "User identifier",
        "validation": {
          "min_length": 3,
          "max_length": 50,
          "pattern": "^[a-zA-Z0-9_-]+$"
        }
      },
      {
        "name": "amount",
        "type": "number",
        "indexed": true,
        "required": false,
        "description": "Transaction amount",
        "validation": {
          "min": 0,
          "max": 999999.99
        }
      },
      {
        "name": "status",
        "type": "string",
        "indexed": true,
        "required": true,
        "description": "Order status",
        "validation": {
          "enum": ["pending", "processing", "completed", "failed"]
        }
      }
    ]
  }
}
```

### Field Definition Properties

| Property      | Type    | Required | Description                                                               |
|---------------|---------|----------|---------------------------------------------------------------------------|
| `name`        | string  | Yes      | Field name (alphanumeric + underscore, must start with letter/underscore) |
| `type`        | string  | Yes      | Data type: `string`, `number`, `boolean`, `array`, `object`, `datetime`   |
| `indexed`     | boolean | Yes      | If true, field is searchable/filterable in Elasticsearch                  |
| `required`    | boolean | Yes      | If true, field must be present in every log                               |
| `description` | string  | No       | Human-readable description                                                |
| `validation`  | object  | No       | Validation rules (see below)                                              |

### Validation Rules

#### String Type
```json
{
  "validation": {
    "min_length": 3,
    "max_length": 100,
    "pattern": "^[A-Z]{2,4}-[0-9]+$",
    "enum": ["value1", "value2", "value3"]
  }
}
```

#### Number Type
```json
{
  "validation": {
    "min": 0,
    "max": 1000
  }
}
```

---

## API Endpoints

### 1. Update Project Schema

**Endpoint:** `PUT /api/projects/{id}/schema`
**Auth:** Required (Bearer token)

**Request:**
```json
{
  "schema": {
    "fields": [
      {
        "name": "user_id",
        "type": "string",
        "indexed": true,
        "required": true,
        "description": "User identifier"
      },
      {
        "name": "action",
        "type": "string",
        "indexed": true,
        "required": true,
        "validation": {
          "enum": ["login", "logout", "purchase", "view"]
        }
      }
    ]
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Schema updated successfully",
  "data": {
    "schema": { /* your schema */ }
  }
}
```

---

### 2. Get Project Schema

**Endpoint:** `GET /api/projects/{id}/schema`
**Auth:** Required (Bearer token)

**Response:**
```json
{
  "success": true,
  "data": {
    "schema": {
      "fields": [ /* field definitions */ ]
    }
  }
}
```

---

### 3. Send Logs

**Endpoint:** `POST /api/v1/logs`
**Auth:** Project token in Authorization header

**Headers:**
```
Authorization: Bearer YOUR_PROJECT_TOKEN
Content-Type: application/json
```

#### Single Log
```json
{
  "timestamp": "2025-11-25T10:30:45.123Z",
  "level": "info",
  "title": "User logged in",
  "data": {
    "user_id": "USR-12345",
    "action": "login"
  }
}
```

#### Batch Logs
```json
{
  "logs": [
    {
      "timestamp": "2025-11-25T10:30:45.123Z",
      "level": "info",
      "title": "User logged in",
      "data": {
        "user_id": "USR-12345",
        "action": "login"
      }
    },
    {
      "timestamp": "2025-11-25T10:30:46.456Z",
      "level": "warning",
      "title": "Invalid login attempt",
      "data": {
        "user_id": "USR-99999",
        "action": "login"
      }
    }
  ]
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Logs received successfully",
  "data": {
    "accepted": 2,
    "project": "My E-commerce App"
  }
}
```

**Validation Error Response:**
```json
{
  "success": false,
  "message": "Some logs failed validation",
  "accepted": 1,
  "rejected": 1,
  "errors": [
    {
      "index": 1,
      "errors": [
        {
          "field": "data.status",
          "error": "Value must be one of: pending, processing, completed, failed"
        },
        {
          "field": "data.amount",
          "error": "Expected type 'number', got 'string'"
        }
      ]
    }
  ]
}
```

---

## Example Schemas for Different Use Cases

### E-commerce Application
```json
{
  "schema": {
    "fields": [
      {"name": "user_id", "type": "string", "indexed": true, "required": true},
      {"name": "order_id", "type": "string", "indexed": true, "required": false},
      {"name": "amount", "type": "number", "indexed": true, "required": false},
      {"name": "currency", "type": "string", "indexed": true, "required": false},
      {"name": "payment_method", "type": "string", "indexed": true, "required": false}
    ]
  }
}
```

### API Gateway
```json
{
  "schema": {
    "fields": [
      {"name": "endpoint", "type": "string", "indexed": true, "required": true},
      {"name": "method", "type": "string", "indexed": true, "required": true},
      {"name": "status_code", "type": "number", "indexed": true, "required": true},
      {"name": "duration_ms", "type": "number", "indexed": true, "required": false},
      {"name": "ip_address", "type": "string", "indexed": true, "required": false}
    ]
  }
}
```

### Authentication Service
```json
{
  "schema": {
    "fields": [
      {"name": "user_id", "type": "string", "indexed": true, "required": false},
      {"name": "action", "type": "string", "indexed": true, "required": true,
       "validation": {"enum": ["login", "logout", "register", "reset_password"]}},
      {"name": "ip_address", "type": "string", "indexed": true, "required": false},
      {"name": "user_agent", "type": "string", "indexed": false, "required": false},
      {"name": "success", "type": "boolean", "indexed": true, "required": true}
    ]
  }
}
```

### Background Jobs
```json
{
  "schema": {
    "fields": [
      {"name": "job_id", "type": "string", "indexed": true, "required": true},
      {"name": "job_type", "type": "string", "indexed": true, "required": true},
      {"name": "queue", "type": "string", "indexed": true, "required": false},
      {"name": "attempts", "type": "number", "indexed": true, "required": false},
      {"name": "duration_ms", "type": "number", "indexed": true, "required": false}
    ]
  }
}
```

---

## Client Examples

### PHP
```php
<?php

$projectToken = 'your_project_token_here';

$log = [
    'timestamp' => date('c'), // ISO 8601
    'level' => 'info',
    'title' => 'Order placed',
    'data' => [
        'user_id' => 'USR-123',
        'order_id' => 'ORD-456',
        'amount' => 199.99,
        'currency' => 'USD'
    ]
];

$ch = curl_init('https://your-logger-host.com/api/v1/logs');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $projectToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($log));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);
```

### JavaScript/Node.js
```javascript
const projectToken = 'your_project_token_here';

const log = {
  timestamp: new Date().toISOString(),
  level: 'info',
  title: 'User action completed',
  data: {
    user_id: 'USR-123',
    action: 'purchase',
    amount: 49.99
  }
};

fetch('https://your-logger-host.com/api/v1/logs', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${projectToken}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(log)
})
.then(res => res.json())
.then(data => console.log(data));
```

### Python
```python
import requests
from datetime import datetime

project_token = 'your_project_token_here'

log = {
    'timestamp': datetime.utcnow().isoformat() + 'Z',
    'level': 'error',
    'title': 'Payment failed',
    'data': {
        'user_id': 'USR-123',
        'order_id': 'ORD-456',
        'error_code': 'INSUFFICIENT_FUNDS'
    }
}

response = requests.post(
    'https://your-logger-host.com/api/v1/logs',
    headers={
        'Authorization': f'Bearer {project_token}',
        'Content-Type': 'application/json'
    },
    json=log
)

print(response.json())
```

---

## Workflow

1. **Create a project** via the panel or API
2. **Define your schema** using `PUT /api/projects/{id}/schema`
3. **Start sending logs** with your project token
4. **Query and filter** logs based on your indexed fields

---

## Notes

- **Schema is mutable**: You can update your schema at any time
- **No schema required initially**: Projects start with no schema, core fields only
- **Indexed fields**: Only indexed fields are searchable in Elasticsearch
- **Validation**: Logs are validated against your schema before storage
- **Storage**: Logs are stored in Elasticsearch only (not in MariaDB)
