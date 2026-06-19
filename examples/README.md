# Examples

Run inside the `composer:2` image (no PHP on the host):

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 php examples/basic-usage.php
```

| Script | Shows | Needs server? |
|---|---|---|
| `basic-usage.php` | Driving the services over PSR-7 with in-memory doubles: list, valid save (existing + create), rejected invalid input (HTTP 200 re-render), delete | No |
