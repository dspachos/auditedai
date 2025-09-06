## Drupal Entity Design: Checklist Question Content Type

### Entity Overview
- **Entity Type**: Node (Content Type)
- **Bundle**: Checklist Question
- **Purpose**: To store individual questions from the "Folha1" sheet, with metadata including cluster reference, ISO 21001 requirements, EQAVET criteria, and documented information needs.

### Fields

| Field Name | Machine Name | Field Type | Description | Required | Cardinality | Additional Settings |
|------------|--------------|------------|-------------|----------|-------------|---------------------|
| **Title** | `title` | Text (Plain) | The question text from the "Question" column (e.g., "Has the organization determined its purpose through a mission and a vision?"). | Yes | Single | Default node title field. Max length: 255 characters. |
| **Cluster** | `field_cluster` | Entity Reference (Taxonomy Term) | References a term in the "VET 21001 Clusters" vocabulary (e.g., "1. Leadership & Strategy"). | Yes | Single | References the `vet_21001_clusters` vocabulary. Auto-create terms disabled. |
| **ISO 21001 Section** | `field_iso_section` | Text (Plain) | Stores the ISO 21001 section reference (e.g., "4.1"). | No | Single | Max length: 50 characters. |
| **ISO Requirements Covered** | `field_iso_requirements` | Text (Long) | Stores detailed ISO requirements (e.g., "4.1, 5.2 a), e)"). | No | Single | Plain text for multi-section references. |
| **ISO Documented Information** | `field_iso_doc_info` | List (Text) | Indicates if documented information is needed for ISO (options: "yes", "no", "empty"). | No | Single | Default: "empty". |
| **EQAVET Criteria** | `field_eqavet_criteria` | Text (Plain) | Stores EQAVET criteria reference (e.g., "1"). | No | Single | Max length: 50 characters. |
| **EQAVET Criteria and Indicators** | `field_eqavet_indicators` | Text (Long) | Stores detailed EQAVET criteria/indicators (e.g., "1.3, 1.7"). | No | Single | Plain text for descriptors. |
| **EQAVET Documented Information** | `field_eqavet_doc_info` | List (Text) | Indicates if documented information is needed for EQAVET (options: "yes", "no", "empty"). | No | Single | Default: "empty". |

### Additional Entity Configurations
- **Revisions**: Enabled for audit trails.
- **Publishing Options**: Default published; optional workflow states (e.g., Draft, Published) if needed.
- **Permissions**:
  - Create/Edit/Delete: Restricted to admin/auditor roles.
  - View: Configurable for authenticated users or specific roles.
- **Display Modes**:
  - Default: Shows all fields (Title, Cluster, ISO/EQAVET details).
  - Teaser: Shows Title and Cluster for lists.
- **Form Settings**: Default node form; fields ordered: Cluster, Title, ISO fields, EQAVET fields.

### Relationships
- **Checklist Question to Cluster**: `field_cluster` links to a single term in the `vet_21001_clusters` vocabulary.