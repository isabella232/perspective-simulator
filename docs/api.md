#API Actions

**Usage for:**

```bash
perspective <action> api <arguments>
```

## Add

Adds a new API specification file.

```bash
perspective [-p] add api <path>
```

### Required arguments:

| Argument |                                                |
| -------- | ---------------------------------------------- |
| path     | The path to a new API specification yaml file. |

## Delete

Deletes the projects API specification file.

```bash
perspective [-p] delete api
```

## Update

Updates the projects API specification file and its operations.

```bash
perspective [-p] update api <path>
```

### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| path     | The path to a new API specification yaml file. If not provided will re-install the current API. |