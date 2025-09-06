# Virtual Patient (VP) for Drupal

**A Drupal module for creating and managing interactive, branching clinical case scenarios for medical education.**

A virtual patient is a computer-based program that simulates real-life clinical scenarios. Learners emulate the roles of healthcare providers to obtain a history, conduct a physical exam, and make diagnostic and therapeutic decisions.

This module provides the framework for building these scenarios as a series of connected nodes, forming a decision tree that students can navigate. It is designed to be flexible, supporting both traditional Drupal site building and decoupled applications (e.g., a mobile player app) via a REST API.

**Note:** This module is under active development. While the core functionality is in place, some features are still being refined.

## Features

*   **Custom Entities:** Provides `Virtual Patient` and `Virtual Patient Node` entities to structure your clinical cases.
*   **Branching Scenarios:** Build complex decision trees by linking nodes together, representing different paths a student can take.
*   **REST API:** Exposes virtual patient data through a REST API, allowing integration with decoupled front-ends like mobile or web applications.
*   **Multilingual Support:** Fully integrated with Drupal's content translation system to create scenarios in multiple languages.
*   **Configurable Player:** Preview and interact with scenarios using a configurable external player application.
*   **Extensible:** Includes several sub-modules to add functionality.

## Sub-modules

*   **VP Analytics (`vp_analytics`):** Provides analytics and insights into how users interact with the virtual patient scenarios.
*   **VP Visual Editor (`vp_visual_editor`):** Offers a visual, drag-and-drop interface for creating and organizing the nodes within a virtual patient scenario.
*   **VP GraphQL (`vp_graphql`):** (In Development) Adds a GraphQL endpoint for querying virtual patient data, offering a more flexible alternative to the REST API.
*   **VP Export (`vp_export`):** (In Development) Allows exporting virtual patient data for research, sharing, or backup purposes.

## Requirements

*   Drupal Core
*   Node
*   REST UI (for easy REST configuration)
*   Serialization
*   Editor
*   Image
*   Content Translation

## Installation

1.  Install the module using Composer:
    ```bash
    composer require drupal/vp
    ```
2.  Enable the **Virtual Patient** module and any desired sub-modules (e.g., `drush en vp vp_visual_editor -y`).

## Configuration

1.  Navigate to **Configuration > Virtual Patient > Settings** (`/admin/config/vp/settings`).
2.  Enter the URL for your front-end player application in the **Player URL** field. This is required for the preview functionality on the virtual patient view page.

## Usage

1.  **Create a Virtual Patient:**
    *   Go to **Content > Add content > Virtual Patient**.
    *   Give your patient case a title and save.

2.  **Build the Scenario:**
    *   After creating the patient, you will be taken to the edit form which includes the **Nodes List**.
    *   **Add a Root Node:** This is the starting point of your scenario. Click "Add root node" to create the first node.
    *   **Create Nodes:** Use the "Create new" button to add more nodes. Each node represents a step, decision point, or piece of information in the scenario.
    *   **Link Nodes:** In each node's edit form, use the "Options" field to link to other nodes, forming the branches of your scenario.
    *   **Terminal Nodes:** Mark nodes as "terminal" to signify the end of a path.

3.  **Translate (Optional):**
    *   Use Drupal's standard content translation workflow to create the patient and its nodes in other languages.

## Contributing

We welcome contributions from the Drupal community! If you’d like to improve the VP module, report issues, or suggest enhancements, please use the project's issue queue on Drupal.org.

*   **Project Page:** `https://www.drupal.org/project/vp`
*   **Issue Queue:** `https://www.drupal.org/project/issues/vp`