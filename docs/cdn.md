# CDN Actions

## Add

Copies a file to the CDN for the project or makes a new directory.

```bash
perspective [-p] add cdn class/directory <path> <locationPath>
```

### Required arguments:

| Argument     |                                                              |
| ------------ | ------------------------------------------------------------ |
| path         | The absolute path to the file to copy or the CDN path for a directory. |
| locationPath | The CDN path to copy the file to, not required when adding a directory. |

## Delete

Deletes the file or directory from the project.

```bash
perspective [-p] delete cdn class/directory <path>
```

### Required arguments:

| Argument |                                                          |
| -------- | -------------------------------------------------------- |
| path     | The path to the file or directory to delete from the CDN |

## Move

Moves a file or directory from the project.

```bash
perspective [-p] move cdn class/directory <oldPath> <newPath>
```

### Required arguments:

| Argument |                                                 |
| -------- | ----------------------------------------------- |
| oldPath  | The current CDN path for the file or directory. |
| newPath  | The new CDN path for the file or directory.     |

## Rename

Renames a file or directory from the project.

```bash
perspective [-p] rename cdn class/directory <oldPath> <newPath>
```

### Required arguments:

| Argument |                                                 |
| -------- | ----------------------------------------------- |
| oldPath  | The current CDN path for the file or directory. |
| newPath  | The new CDN path for the file or directory.     |