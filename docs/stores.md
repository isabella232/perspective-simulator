# Store Actions

## Data Stores
**Usage for:**
```bash
perspective <action> datastore <arguments>
```
### Add
Adds a new data store for use in the project

```bash
perspective [-p] add datastore <storeName>
```

#### Required arguments:
| Argument  |                                          |
| --------- | ---------------------------------------- |
| storeName | The name of the new store to be created. |

### Delete
Deletes a data store from the project

```bash
perspective [-p] delete datastore <storeName>
```
#### Required arguments:
​| Argument  |                                          |
| --------- | ---------------------------------------- |
| storeName | The name of the new store to be created. |


### Delete
Renames a data store in the project

```bash
perspective [-p] rename datastore <oldName> <newName>
```
#### Required arguments:
​| Argument |                                     |
| -------- | ----------------------------------- |
| oldName  | The current name of the data store. |
| newName  | The new name of the data store.     |

### Add Reference
Adds a reference between two stores

```bash
perspective [-p] addReference datastore <referneceName> <targetCode> <sourceType> <sourceCode> <cardinatlity>
```

#### Required arguments:
| Argument      |                                                       |
| ------------- | ----------------------------------------------------- |
| referneceName | The name of the reference we are adding.              |
| targetCode    | The name of the store the reference will be added to. |
| sourceType    | The type of store being referenced.                   |
| sourceCode    | The name of the store being referenced.               |


#### Optional arguments:
| Argument     |                                                                                           |
| ------------ | ----------------------------------------------------------------------------------------- |
| cardinatlity | The cardinatlity of the reference, eg. 1:1, 1:M or M:M, if not provided M:M will be used. |

### Rename Reference
Renames the reference between two stores

```bash
perspective [-p] renameReference datastore <targetCode> <oldName> <newName>
```

#### Required arguments:
| Argument   |                                                 |
| ---------- | ----------------------------------------------- |
| targetCode | The name of the store the reference belongs to. |
| oldName    | The current name of the reference.              |
| newName    | The new name for the reference.                 |

### Delete Reference
Deletes the reference between two stores

```bash
perspective [-p] deleteReference datastore <referneceName> <targetCode>
```

#### Required arguments:
| Argument      |                                                           |
| ------------- | --------------------------------------------------------- |
| referneceName | The name of the reference we are deleting.                |
| targetCode    | The name of the store the reference will be deleted from. |


## User Stores
**Usage for:**
```bash
perspective <action> userstore <arguments>
```
### Add
Adds a new user store for use in the project

```bash
perspective [-p] add userstore <storeName>
```

#### Required arguments:
| Argument  |                                          |
| --------- | ---------------------------------------- |
| storeName | The name of the new store to be created. |

### Delete
Deletes a user store from the project

```bash
perspective [-p] delete userstore <storeName>
```
#### Required arguments:
​| Argument  |                                          |
| --------- | ---------------------------------------- |
| storeName | The name of the new store to be created. |


### Delete
Renames a user store in the project

```bash
perspective [-p] rename userstore <oldName> <newName>
```
#### Required arguments:
​| Argument |                                     |
| -------- | ----------------------------------- |
| oldName  | The current name of the user store. |
| newName  | The new name of the user store.     |

### Add Reference
Adds a reference between two stores

```bash
perspective [-p] addReference userstore <referneceName> <targetCode> <sourceType> <sourceCode> <cardinatlity>
```

#### Required arguments:
| Argument      |                                                       |
| ------------- | ----------------------------------------------------- |
| referneceName | The name of the reference we are adding.              |
| targetCode    | The name of the store the reference will be added to. |
| sourceType    | The type of store being referenced.                   |
| sourceCode    | The name of the store being referenced.               |


#### Optional arguments:
| Argument     |                                                                                           |
| ------------ | ----------------------------------------------------------------------------------------- |
| cardinatlity | The cardinatlity of the reference, eg. 1:1, 1:M or M:M, if not provided M:M will be used. |

### Rename Reference
Renames the reference between two stores

```bash
perspective [-p] renameReference userstore <targetCode> <oldName> <newName>
```

#### Required arguments:
| Argument   |                                                 |
| ---------- | ----------------------------------------------- |
| targetCode | The name of the store the reference belongs to. |
| oldName    | The current name of the reference.              |
| newName    | The new name for the reference.                 |

### Delete Reference
Deletes the reference between two stores

```bash
perspective [-p] deleteReference userstore <referneceName> <targetCode>
```

#### Required arguments:
| Argument      |                                                           |
| ------------- | --------------------------------------------------------- |
| referneceName | The name of the reference we are deleting.                |
| targetCode    | The name of the store the reference will be deleted from. |
