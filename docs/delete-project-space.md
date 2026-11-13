# Delete a Project Space (Manual SQL)

There is currently no GUI or API endpoint for deleting project spaces. Use these queries to delete a space and its linked data manually.

Replace `SPACE_ID_HERE` with the actual project space ID.

```sql
-- 1. Clean up expert requests (no cascade FK)
DELETE FROM ib_expert_requests WHERE space_id = 'SPACE_ID_HERE';

-- 2. Delete moderation reviews for the linked idea(s)
DELETE FROM ib_moderation_reviews WHERE idea_id IN (
    SELECT id FROM ib_project_ideas WHERE project_space_id = 'SPACE_ID_HERE'
);

-- 3. Delete the idea(s)
DELETE FROM ib_project_ideas WHERE project_space_id = 'SPACE_ID_HERE';

-- 4. Delete the space (cascades to members, collaboards, updates)
DELETE FROM ib_project_spaces WHERE id = 'SPACE_ID_HERE';
```

## What gets deleted

| Table | Method |
|---|---|
| `ib_expert_requests` | Manual (no FK cascade) |
| `ib_moderation_reviews` | Manual (linked via idea) |
| `ib_project_ideas` | Manual |
| `ib_project_spaces` | Manual |
| `ib_project_space_members` | Automatic (ON DELETE CASCADE) |
| `ib_project_space_collaboards` | Automatic (ON DELETE CASCADE) |
| `ib_project_space_updates` | Automatic (ON DELETE CASCADE) |
