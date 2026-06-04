# Data Models

The application uses Active Record pattern models for database access. All models are located in `src/classes/`.

## Common Pattern

All models follow the same pattern:

```php
<?php
require_once 'DB.php';
require_once 'Author.php';

// Find all records
$authors = Author::findAll();

// Find by ID
$author = Author::findById(1);

// Create new record
$author = new Author([
    'first_name' => 'John',
    'last_name' => 'Doe'
]);
$author->save();  // INSERT into database

// Update existing record
$author->first_name = 'Jane';
$author->save();  // UPDATE in database

// Delete record
$author->delete();
?>
```

## Author Model

**Properties:**

- `id` (int) - Auto-generated primary key
- `first_name` (string) - Author's first name
- `last_name` (string) - Author's last name

**Methods:**

| Method                  | Description                 | Returns        |
| ----------------------- | --------------------------- | -------------- |
| `save()`                | Insert or update author     | `void`         |
| `delete()`              | Delete author from database | `void`         |
| `Author::findAll()`     | Get all authors             | `Author[]`     |
| `Author::findById($id)` | Find author by ID           | `Author\|null` |

**Example:**

```php
// Create new author
$author = new Author([
    'first_name' => 'Michael',
    'last_name' => 'Smith'
]);
$author->save();

// Get all authors
$authors = Author::findAll();
foreach ($authors as $author) {
    echo "{$author->first_name} {$author->last_name}\n";
}

// Find specific author
$author = Author::findById(5);
if ($author) {
    $author->first_name = 'Mike';
    $author->save();
}
```

## Category Model

**Properties:**

- `id` (int) - Auto-generated primary key
- `name` (string) - Category name

**Methods:**

| Method                    | Description                   | Returns          |
| ------------------------- | ----------------------------- | ---------------- |
| `save()`                  | Insert or update category     | `void`           |
| `delete()`                | Delete category from database | `void`           |
| `Category::findAll()`     | Get all categories            | `Category[]`     |
| `Category::findById($id)` | Find category by ID           | `Category\|null` |

**Example:**

```php
// Create new category
$category = new Category(['name' => 'Technology']);
$category->save();

// Get all categories
$categories = Category::findAll();

// Update category
$category = Category::findById(1);
$category->name = 'Tech News';
$category->save();
```

## Location Model

**Properties:**

- `id` (int) - Auto-generated primary key
- `name` (string) - Location name

**Methods:**

| Method                    | Description                   | Returns          |
| ------------------------- | ----------------------------- | ---------------- |
| `save()`                  | Insert or update location     | `void`           |
| `delete()`                | Delete location from database | `void`           |
| `Location::findAll()`     | Get all locations             | `Location[]`     |
| `Location::findById($id)` | Find location by ID           | `Location\|null` |

**Example:**

```php
// Create new location
$location = new Location(['name' => 'London']);
$location->save();

// Find and delete location
$location = Location::findById(3);
if ($location) {
    $location->delete();
}
```

## Story Model

**Properties:**

- `id` (int) - Auto-generated primary key
- `headline` (string) - Story headline
- `short_headline` (string) - Abbreviated headline
- `subheadline` (string) - Story subheadline
- `article` (string) - Full article text
- `img_url` (string) - Image URL
- `author_id` (int) - Foreign key to authors table
- `category_id` (int) - Foreign key to categories table
- `location_id` (int) - Foreign key to locations table
- `created_at` (datetime) - Auto-generated creation timestamp
- `updated_at` (datetime) - Auto-generated update timestamp

**Methods:**

| Method                                 | Description                 | Returns       |
| -------------------------------------- | --------------------------- | ------------- |
| `save()`                               | Insert or update story      | `void`        |
| `delete()`                             | Delete story from database  | `void`        |
| `Story::findAll($options)`             | Get all stories             | `Story[]`     |
| `Story::findById($id)`                 | Find story by ID            | `Story\|null` |
| `Story::findByAuthor($id, $options)`   | Find stories by author ID   | `Story[]`     |
| `Story::findByCategory($id, $options)` | Find stories by category ID | `Story[]`     |
| `Story::findByLocation($id, $options)` | Find stories by location ID | `Story[]`     |

**Options Parameter:**

The `$options` parameter accepts an associative array:

```php
[
    'order_by' => 'created_at',  // column to order by
    'order'    => 'DESC',        // direction: 'ASC' (default) or 'DESC'
    'limit'    => 10,            // LIMIT clause
    'offset'   => 5              // OFFSET clause (requires limit)
]
```

**Example:**

```php
// Create new story
$story = new Story([
    'headline' => 'Breaking News',
    'article' => 'This is the full article text...',
    'img_url' => 'https://example.com/image.jpg',
    'author_id' => 1,
    'category_id' => 2,
    'location_id' => 3
]);
$story->save();

// Get all stories
$stories = Story::findAll();

// Get stories with pagination
$stories = Story::findAll([
    'order_by' => 'created_at',
    'order'    => 'DESC',
    'limit'    => 10,
    'offset'   => 0
]);

// Find stories by author
$authorStories = Story::findByAuthor(5);

// Find stories by category with limit
$categoryStories = Story::findByCategory(2, ['limit' => 5]);

// Update story
$story = Story::findById(10);
if ($story) {
    $story->headline = 'Updated Headline';
    $story->save();  // updated_at automatically set
}

// Delete story
$story->delete();
```

## Error Handling

All models throw exceptions on database errors:

```php
try {
    $author = new Author(['first_name' => 'John', 'last_name' => 'Doe']);
    $author->save();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```
