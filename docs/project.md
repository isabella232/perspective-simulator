# Project Actions
**Usage for:**

```bash
perspective <action> project <arguments>
```

## Add

Creates a new project.

```bash
perspective [-p] add project <name> <namespace> <path>
```
### Required arguments:
| Argument  |                                    |
| --------- | ---------------------------------- |
| name      | The name for the new Project.      |
| namespace | The namespace for the new Project. |
| path      | The web path for the new Project.  |

## Delete

Deletes a project.

```bash
perspective [-p] delete project <namespace>
```

### Required arguments:
| Argument  |                                    |
| --------- | ---------------------------------- |
| namespace | The namespace for the new Project. |

## Update

Updates a project setting.

```bash
perspective [-p] update project <namespace> <setting> <value>
```

### Required arguments:
| Argument  |                                    |
| --------- | ---------------------------------- |
| namespace | The namespace for the new Project. |
| setting   | The setting we are updating.       |
| value     | The new value for the setting.     |
