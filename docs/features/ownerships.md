# Ownerships

Ownerships represent a user's claim to manage an organizer (and its associated events) in UiTdatabank. An ownership goes through a request/approval workflow and, when approved, grants the owner permissions via a role.

## State machine

An ownership has four possible states:

```
requested ──→ approved ──→ deleted
    │                        ↑
    ├──→ rejected ──────────→┘
    │
    └──→ deleted
```

- `requested` → `approved`: only from requested
- `requested` → `rejected`: only from requested
- `requested` / `approved` / `rejected` → `deleted`: from any non-deleted state

An approved ownership **cannot** be rejected. The `reject()` method only acts on ownerships in the `requested` state. To revoke an approved ownership, use `delete()` instead.

A rejected or deleted ownership allows the same owner to request ownership again for the same item.

## Aggregate

`src/Ownership/Ownership.php`

Event-sourced aggregate root (Broadway). Methods:

| Method              | Event dispatched      | Allowed from state |
|---------------------|-----------------------|--------------------|
| `requestOwnership`  | `OwnershipRequested`  | (initial)          |
| `approve`           | `OwnershipApproved`   | requested          |
| `reject`            | `OwnershipRejected`   | requested          |
| `delete`            | `OwnershipDeleted`    | any except deleted  |

## Events

All events live in `src/Ownership/Events/`.

| Event                | Payload fields                                        |
|----------------------|-------------------------------------------------------|
| `OwnershipRequested` | id, itemId, itemType, ownerId, requesterId            |
| `OwnershipApproved`  | id                                                    |
| `OwnershipRejected`  | id                                                    |
| `OwnershipDeleted`   | id                                                    |

## Side effects

### 1. Role creation / permission management

**Projector:** `src/Ownership/Readmodels/OwnershipPermissionProjector.php`

A role is a **per-item entity**, not a per-user entity. It holds the search constraint and permissions that grant access to an organizer and its events. Users are added to and removed from the role as ownerships are approved and deleted. The role itself is never deleted — it is a stable anchor that can be shared by multiple owners and reused across ownership lifecycles.

When a new ownership is approved, the projector looks for an existing ownership on the same item (via `getExistingRoleId`) to find a reusable role. If one exists, the user is simply added to it. If not, a new role is created with the item's constraint and permissions.

| Event              | Action                                                                                     |
|--------------------|--------------------------------------------------------------------------------------------|
| OwnershipApproved  | Creates a role (or adds user to existing role for the same item), stores `roleId` on ownership |
| OwnershipRejected  | No action (no role exists yet since rejection can only happen from `requested` state)      |
| OwnershipDeleted   | Removes user from role (if `roleId` is set), nullifies `roleId` on ownership               |

**On approval (new role):**
1. `CreateRole` with name `"Beheerders organisatie {organizer name}"`
2. `AddUser` to the new role
3. `AddConstraint` with query `(id:{itemId} OR (organizer.id:{itemId} AND _type:event))`
4. `AddPermission` for `organisatiesBewerken`
5. `AddPermission` for `aanbodBewerken`

**On approval (existing role for same item):**
1. `AddUser` to the existing role

**On deletion (with roleId):**
1. `RemoveUser` from the role (the role itself is not deleted)

The `RemoveUser` command flows through the Role aggregate (`Role::removeUser()` emits `UserRemoved`) and is handled by three role projectors (`RoleUsersProjector`, `UserRolesProjector`, `UserPermissionsProjector`), which all only update the user-role association. None of them check remaining user count or trigger role deletion. Roles can only be deleted manually via the `DeleteRole` API endpoint (`DELETE /roles/{roleId}`). Deleting a role removes all its read model projections (detail, search, users, labels, permissions) but retains the events in the event store (standard event sourcing pattern).

**Deleting one ownership does not affect other ownerships sharing the same role.** The `applyOwnershipDeleted` handler only removes that specific ownership's user from the role and nullifies the `roleId` on that ownership. Other ownerships for the same item retain their `roleId` and their users remain on the role. If all ownerships for an item are deleted, the role persists with zero users. A subsequent ownership approval for the same item will not find an existing `roleId` (all are nullified) and will create a new role.

### 2. Mail sending

**Handler:** `src/Mailer/Handler/SendMailsForOwnershipEventHandler.php`

Mails are dispatched asynchronously. Skipped during replay or when mails are disabled.

| Event              | Mail command                  | Recipient        |
|--------------------|-------------------------------|------------------|
| OwnershipRequested | `SendOwnershipRequestedMail`  | Owner            |
| OwnershipApproved  | `SendOwnershipAcceptedMail`   | Owner            |
| OwnershipRejected  | `SendOwnershipRejectedMail`   | Owner            |
| OwnershipDeleted   | No mail                       | -                |

### 3. Organizer owner change

**Handler:** `src/Http/Ownership/ApproveOwnershipRequestHandler.php`

On approval, if the organizer has no `creator` field in its JSON-LD document, a `ChangeOwner` command is dispatched to set the ownership on the organizer itself.

## Read models / Projections

### OwnershipSearchProjector

`src/Ownership/Readmodels/OwnershipSearchProjector.php`

Maintains the `ownership_search` database table. Updated on all four events. Fields: `id`, `item_id`, `item_type`, `owner_id`, `state`, `role_id`, `created`.

### OwnershipLDProjector

`src/Ownership/Readmodels/OwnershipLDProjector.php`

Maintains JSON-LD documents for API responses. On state transitions, adds metadata such as `approvedById`, `rejectedById`, `deletedById`, corresponding emails, and timestamps.

## API endpoints

| Method | Endpoint                              | Handler                             |
|--------|---------------------------------------|-------------------------------------|
| POST   | `/ownerships`                         | `RequestOwnershipRequestHandler`    |
| POST   | `/ownerships/{ownershipId}/approve`   | `ApproveOwnershipRequestHandler`    |
| POST   | `/ownerships/{ownershipId}/reject`    | `RejectOwnershipRequestHandler`     |
| DELETE | `/ownerships/{ownershipId}`           | `DeleteOwnershipRequestHandler`     |
| GET    | `/ownerships/{ownershipId}`           | `GetOwnershipRequestHandler`        |
| GET    | `/ownerships`                         | `SearchOwnershipRequestHandler`     |

Authorization is handled by `OwnershipStatusGuard`.

## Duplicate prevention

A new ownership request is rejected with `409 Conflict` if an open (`requested` or `approved`) ownership already exists for the same item and owner. Re-requesting is allowed after rejection or deletion.