# Architecture

UiTdatabank 3 (UDB3) is an event-sourced backend built with Broadway.

## Tech Stack

- **Language**: PHP 8.1+
- **Architecture**: Event Sourcing with [Broadway](https://github.com/broadway/broadway)
- **Database**: Doctrine DBAL
- **Message Broker**: RabbitMQ (AMQP)
- **Search**: SAPI3 (external Elasticsearch-based service)
- **Cache**: Redis
- **Testing**: PHPUnit (unit), Behat (acceptance)
- **Quality**: PHPStan (static analysis), PHP-CS-Fixer (code style)
- **Environment**: Docker with docker-compose

## Project Structure

```
├── app/                 # Service providers and application wiring
├── src/                 # Core domain code
├── tests/               # PHPUnit tests (mirrors src/ structure)
├── features/            # Behat acceptance tests
├── docker/              # Docker configuration
├── docs/                # Documentation
│   └── refactor/        # Refactoring plans and progress
└── vendor/              # Dependencies (never modify)
```

## Event Sourcing with Broadway

- **Aggregates**: Domain objects that emit events (in `src/`)
- **Projectors**: Build read models from events
- **Event Bus**: Distributes events to handlers

## Message Flow: Domain Events to Search Indexing

```
1. API Request (e.g., Create Event)
   ↓
2. Command Handler processes command
   ↓
3. Domain event created (e.g., EventCreated)
   ↓
4. EventBus publishes event to subscribers
   ↓
5. Projectors generate JSON-LD → emit *ProjectedToJSONLD events
   ↓
6. AMQPPublisher sends message to RabbitMQ
   ↓
7. External SAPI3 consumes message and indexes in Elasticsearch
```

### AMQP Configuration

- **Exchange**: `udb3.x.domain-events`
- **Routing**: Messages routed to `api`, `cli`, or `related` queues based on context
- **Message Types**:
  - `application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json`
  - `application/vnd.cultuurnet.udb3-events.place-projected-to-jsonld+json`
  - `application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json`

### SAPI3 Integration

SAPI3 is an external search service (Elasticsearch-based) that:
- Consumes messages from RabbitMQ
- Indexes events, places, and organizers
- Provides search endpoints proxied by UDB3

**Configuration** (in `config.php`):
- Base URL: `http://search.uitdatabank.local:9000`
- ES URL: `http://search.uitdatabank.local:9200/`
- API Key: configured per environment

**Search Endpoints** (proxy to SAPI3):
- `/events` - Search events
- `/places` - Search places
- `/organizers` - Search organizers
- `/offers` - Search events and places combined

## Docker Services

| Service | Port | Purpose |
|---------|------|---------|
| PHP | 80 | Application |
| MySQL | 3306 | Database |
| Redis | 6379 | Cache |
| RabbitMQ | 5672, 15672 | Message broker (AMQP, Management UI) |
| Mailpit | 1025, 8025 | Email testing (SMTP, Web UI) |
