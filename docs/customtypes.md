# Custom Type Actions

## Custom Data Type

**Usage for:**

```bash
perspective <action> customtype customdatatype <arguments>
```

### Add

Adds a new Custom Data Type to the project

```bash
perspective [-p] add customtype customdatatype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

#### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

### Delete

Deletes a Custom Data Type from the project

```bash
perspective [-p] delete customtype customdatatype <customTypeCode>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Rename

Renames a Custom Data Type in the project

```bash
perspective [-p] rename customtype customdatatype <oldCustomTypeCode> <newCustomTypeCode>
```

#### Required arguments:

| Argument          |                                       |
| ----------------- | ------------------------------------- |
| oldCustomTypeCode | The current code for the custom type. |
| newCustomTypeCode | The new code for the custom type.     |

### Move

Moves a Custom Data Type in the project

```bash
perspective [-p] move customtype customdatatype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

## Custom Page Type

**Usage for:**

```bash
perspective <action> customtype custompagetype <arguments>
```

### Add

Adds a new Custom Page Type to the project

```bash
perspective [-p] add customtype custompagetype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

#### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

### Delete

Deletes a Custom Page Type from the project

```bash
perspective [-p] delete customtype custompagetype <customTypeCode>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Rename

Renames a Custom Page Type in the project

```bash
perspective [-p] rename customtype custompagetype <oldCustomTypeCode> <newCustomTypeCode>
```

#### Required arguments:

| Argument          |                                       |
| ----------------- | ------------------------------------- |
| oldCustomTypeCode | The current code for the custom type. |
| newCustomTypeCode | The new code for the custom type.     |

### Move

Moves a Custom Page Type in the project

```bash
perspective [-p] move customtype custompagetype <customTypeCode> <parent>
```

#### Required arguments:

| Argument       |                         |
| -------------- | ----------------------- |
| customTypeCode | The custom type's code. |

### Optional arguments:

| Argument |                                                              |
| -------- | ------------------------------------------------------------ |
| parent   | The parent type code, if not provided the default type's parent will be used. |

