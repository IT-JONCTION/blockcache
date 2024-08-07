### Issue Description

**Title:** Implement `HasCacheKey` Trait for Vanilla PHP Projects

**Description:**

We need to implement a `HasCacheKey` trait that can be used in any vanilla PHP project. This trait will provide methods for generating cache keys and updating parent model timestamps. The implementation should be flexible, allowing the trait to fetch necessary data using PDO and accommodate different table names and primary keys.

**Tasks:**

1. **Trait Definition**:
    - Add a `_cache` suffix to `id` and `updated_at` properties to avoid naming clashes.
    - Use PDO to fetch `id_cache` and `updated_at_cache` values.
    - Implement `getCacheKey` method:
        - Fetch the model's `id` and `updated_at` values using PDO.
        - Generate and return a cache key string based on the class name, `id`, and `updated_at` timestamp.
    - Implement `touchParent` method:
        - Update the `updated_at` timestamp of a parent model in the database using PDO.

2. **Parameters**:
    - Both methods (`getCacheKey` and `touchParent`) should accept PDO, table name, and primary ID as parameters.
    - Provide sensible default values for table name and primary ID if not supplied.

3. **Model Example**:
    - Create an example model class using the `HasCacheKey` trait.
    - Demonstrate updating a model and touching a parent model.

4. **Unit Tests**:
    - Write PHPUnit tests to verify the functionality of the trait.
    - Test `getCacheKey` method:
        - Ensure the method correctly fetches data and generates the expected cache key.
    - Test `touchParent` method:
        - Ensure the method correctly updates the parent's `updated_at` timestamp in the database.

**Example Usage:**

```php
class Post
{
    use HasCacheKey;

    protected $id_cache;
    protected $updated_at_cache;

    public function __construct($id_cache = null, $updated_at_cache = null)
    {
        $this->id_cache = $id_cache;
        $this->updated_at_cache = $updated_at_cache;
    }

    public function updatePost(PDO $pdo, $newData, $parentTable, $parentId)
    {
        // Update the post in the database and refresh updated_at_cache
        // Touch the parent
        $this->touchParent($pdo, $parentTable, $parentId);
    }
}
```

**Tests:**

```php
class HasCacheKeyTest extends TestCase
{
    protected $pdo;
    protected $model;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->model = new class {
            use HasCacheKey;

            protected $id_cache = 1;
            protected $updated_at_cache;

            public function __construct($id_cache = null, $updated_at_cache = null)
            {
                $this->id_cache = $id_cache;
                $this->updated_at_cache = $updated_at_cache;
            }
        };
    }

    public function testGetCacheKey()
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['id' => 1]);
        $stmt->expects($this->once())->method('fetch')->willReturn(['id' => 1, 'updated_at' => '2023-01-01 00:00:00']);
        $this->pdo->expects($this->once())->method('prepare')->with("SELECT id, updated_at FROM test_table WHERE id = :id")->willReturn($stmt);

        $expectedCacheKey = get_class($this->model) . '/1-' . strtotime('2023-01-01 00:00:00');
        $this->assertEquals($expectedCacheKey, $this->model->getCacheKey($this->pdo, 'test_table', 1));
    }

    public function testTouchParent()
    {
        $parentTable = 'parent_table';
        $parentId = 1;
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with(['id' => $parentId]);
        $this->pdo->expects($this->once())->method('prepare')->with("UPDATE $parentTable SET updated_at = NOW() WHERE id = :id")->willReturn($stmt);

        $this->model->touchParent($this->pdo, $parentTable, $parentId);
    }
}
```

**Additional Notes:**

- Ensure proper exception handling for database operations.
- Update documentation to include usage examples and testing instructions.

**Labels:**

- enhancement
- trait
- PHP
- caching
- PDO

Please let me know if there are any additional requirements or changes needed for this task.
