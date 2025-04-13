# Task Tracker API

A clean, scalable, and extensible Task Tracker API service built with Symfony 6.4.

## Architecture Overview

The application follows a layered architecture with clear separation of concerns:

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Controller │────▶│   Service   │────▶│ Repository  │────▶│   Storage   │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

### Layers and Responsibilities

1. **Controller Layer**
   - Handles HTTP requests and responses
   - Validates input data
   - Maps HTTP requests to service calls
   - Returns appropriate HTTP responses

2. **Service Layer**
   - Contains business logic
   - Orchestrates operations between repositories
   - Validates business rules and state transitions
   - Handles transactions

3. **Repository Layer**
   - Abstracts data access
   - Implements data persistence logic
   - Provides a clean interface for data operations

4. **Entity Layer**
   - Represents domain objects with immutable state
   - Contains business rules and validation
   - Implements state transitions through immutable patterns

### Design Patterns Applied

1. **Repository Pattern**
   - Abstracts data access
   - Makes the application storage-agnostic
   - Easy to switch between different storage implementations

2. **DTO Pattern**
   - Separates data transfer objects from domain entities
   - Provides clear boundaries between layers
   - Prevents over-fetching of data

3. **Dependency Injection**
   - Promotes loose coupling
   - Makes the code more testable
   - Follows the Dependency Inversion Principle

4. **Interface Segregation**
   - Repository interface defines clear contracts
   - Makes it easy to implement different storage solutions

5. **Immutable Objects**
   - Task entities are immutable
   - State changes create new instances
   - Ensures thread safety and predictable behavior

## API Endpoints

- `POST /api/tasks` - Create a new task
- `GET /api/tasks` - List all tasks (with optional filters)
- `GET /api/tasks/{id}` - Get a specific task
- `PATCH /api/tasks/{id}/status` - Update task status
- `PATCH /api/tasks/{id}/assign` - Assign task to a user

### Error Handling

The API provides clear error messages.

## Getting Started

1. Install dependencies:
   ```bash
   composer install
   ```

2. Start the development server:
   ```bash
   symfony server:start
   ```

3. Run tests:
   ```bash
   ./vendor/bin/phpunit
   ```

4. The API will be available at `http://localhost:8000`

## Scalability & Extensibility

The architecture is designed to be easily extensible:

1. **Adding Comments**
   - Create a new `Comment` entity
   - Add `CommentRepositoryInterface` and implementation
   - Create `CommentService` for business logic
   - Add `CommentController` for API endpoints

2. **User Roles**
   - Add role field to User entity
   - Implement role-based access control in services
   - Add role validation in controllers
   - Create role-specific endpoints

3. **Database Persistence**
   - Create a new repository implementation using Doctrine
   - Configure database connection
   - Add migrations for schema changes
   - Update service layer to handle database transactions

## Testing

The code is thoroughly tested