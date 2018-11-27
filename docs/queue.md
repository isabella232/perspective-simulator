# Queue Actions

**Usage for:**

```bash
perspective <action> queue <arguments>
```

## Add

Adds a new Queue to a project.

```bash
perspective [-p] add queue <queueName>
```

### Required arguments:

| Argument  |                             |
| --------- | --------------------------- |
| queueName | The name for the new queue. |

## Delete

Deletes a queue in a project.

```bash
perspective [-p] delete queue <queueName>
```
### Required arguments:

| Argument  |                                       |
| --------- | ------------------------------------- |
| queueName | The name for the queue being deleted. |

## Rename

Renames a queue.

```bash
perspective [-p] rename queue <oldName> <newName>
```
| Argument |                                |
| -------- | ------------------------------ |
| oldName  | The current name of the queue. |
| newName  | The new name for the queue.    |
