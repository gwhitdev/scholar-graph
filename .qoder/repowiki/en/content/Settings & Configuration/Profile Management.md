# Profile Management

<cite>
**Referenced Files in This Document**
- [ProfileController.php](file://app/Http/Controllers/Settings/ProfileController.php)
- [ProfileUpdateRequest.php](file://app/Http/Requests/Settings/ProfileUpdateRequest.php)
- [ProfileDeleteRequest.php](file://app/Http/Requests/Settings/ProfileDeleteRequest.php)
- [ProfileValidationRules.php](file://app/Concerns/ProfileValidationRules.php)
- [PasswordValidationRules.php](file://app/Concerns/PasswordValidationRules.php)
- [profile.tsx](file://resources/js/pages/settings/profile.tsx)
- [delete-user.tsx](file://resources/js/components/delete-user.tsx)
- [settings.php](file://routes/settings.php)
- [User.php](file://app/Models/User.php)
- [HandleInertiaRequests.php](file://app/Http/Middleware/HandleInertiaRequests.php)
- [auth.php](file://config/auth.php)
- [ProfileUpdateTest.php](file://tests/Feature/Settings/ProfileUpdateTest.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)

## Introduction
This document provides comprehensive documentation for the profile management system, focusing on the ProfileController implementation, validation rules, and frontend integration. It explains profile editing, updates, and deletion functionality, details the ProfileUpdateRequest and ProfileDeleteRequest validation rules and business logic, and describes the profile settings page implementation with form handling, email verification status display, and success messaging. Examples of profile update workflows, email change handling with verification status reset, and user account deletion processes are included, along with integration details for the authentication system and session management during profile changes.

## Project Structure
The profile management system spans backend PHP controllers and requests, frontend React components, routing configuration, and shared data through Inertia middleware. The key components are organized as follows:
- Backend: ProfileController handles GET/POST requests for profile editing and updates, and DELETE requests for account deletion.
- Validation: ProfileUpdateRequest and ProfileDeleteRequest encapsulate validation rules using reusable traits.
- Frontend: The profile settings page renders forms, displays email verification status, and integrates with the delete account component.
- Routing: Routes define authenticated access for profile operations and require email verification for destructive actions.
- Shared Data: Inertia middleware shares authenticated user data and application configuration across requests.

```mermaid
graph TB
subgraph "Backend"
PC["ProfileController<br/>Handles edit/update/destroy"]
PUR["ProfileUpdateRequest<br/>Validates profile updates"]
PDR["ProfileDeleteRequest<br/>Validates account deletion"]
U["User Model<br/>Eloquent user entity"]
PV["ProfileValidationRules<br/>Reusable validation rules"]
PW["PasswordValidationRules<br/>Password validation rules"]
end
subgraph "Frontend"
PS["Profile Settings Page<br/>React component"]
DU["Delete User Component<br/>React component"]
end
subgraph "Routing & Middleware"
RT["Routes<br/>settings.php"]
IM["Inertia Middleware<br/>HandleInertiaRequests"]
AC["Auth Config<br/>auth.php"]
end
PS --> PC
DU --> PC
PC --> PUR
PC --> PDR
PUR --> PV
PDR --> PW
PC --> U
RT --> PC
IM --> PS
IM --> DU
AC --> IM
```

**Diagram sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [ProfileValidationRules.php:9-51](file://app/Concerns/ProfileValidationRules.php#L9-L51)
- [PasswordValidationRules.php:8-29](file://app/Concerns/PasswordValidationRules.php#L8-L29)
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:8-47](file://app/Http/Middleware/HandleInertiaRequests.php#L8-L47)
- [auth.php:5-117](file://config/auth.php#L5-L117)

**Section sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [ProfileValidationRules.php:9-51](file://app/Concerns/ProfileValidationRules.php#L9-L51)
- [PasswordValidationRules.php:8-29](file://app/Concerns/PasswordValidationRules.php#L8-L29)
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:8-47](file://app/Http/Middleware/HandleInertiaRequests.php#L8-L47)
- [auth.php:5-117](file://config/auth.php#L5-L117)

## Core Components
This section documents the primary components involved in profile management, including the controller actions, validation requests, frontend rendering, and routing configuration.

- ProfileController: Implements edit, update, and destroy actions with proper authentication and session handling.
- ProfileUpdateRequest: Applies profile-specific validation rules using shared validation traits.
- ProfileDeleteRequest: Enforces current password verification for account deletion.
- Profile Settings Page: Renders the profile update form, displays email verification status, and shows success messages.
- Delete User Component: Provides a modal interface for confirming account deletion with password validation.
- Routes: Define authenticated access for profile operations and require email verification for destructive actions.
- Inertia Middleware: Shares authenticated user data and application configuration across requests.
- User Model: Defines the Eloquent user entity with email verification and two-factor authentication support.

**Section sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:36-46](file://app/Http/Middleware/HandleInertiaRequests.php#L36-L46)
- [User.php:32-49](file://app/Models/User.php#L32-L49)

## Architecture Overview
The profile management system follows a layered architecture:
- Presentation Layer: React components render the profile settings page and delete account interface.
- Application Layer: ProfileController orchestrates business logic for profile updates and deletions.
- Domain Layer: Eloquent User model persists user data and maintains verification state.
- Infrastructure Layer: Inertia middleware shares authenticated user data, while routing enforces authentication and verification policies.

```mermaid
sequenceDiagram
participant Client as "Browser"
participant Router as "Routes/settings.php"
participant Controller as "ProfileController"
participant Validator as "ProfileUpdateRequest"
participant User as "User Model"
participant Session as "Session"
Client->>Router : GET /settings/profile
Router->>Controller : edit()
Controller->>Session : Read mustVerifyEmail, status
Controller-->>Client : Render profile page
Client->>Router : PATCH /settings/profile
Router->>Controller : update(ProfileUpdateRequest)
Controller->>Validator : validated data
Validator-->>Controller : validated attributes
Controller->>User : fill(validated)
alt email changed
Controller->>User : email_verified_at = null
end
Controller->>User : save()
Controller->>Session : flash success toast
Controller-->>Client : Redirect to profile.edit
```

**Diagram sources**
- [settings.php:8-13](file://routes/settings.php#L8-L13)
- [ProfileController.php:20-44](file://app/Http/Controllers/Settings/ProfileController.php#L20-L44)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [User.php:32-49](file://app/Models/User.php#L32-L49)

## Detailed Component Analysis

### ProfileController Implementation
The ProfileController manages profile-related operations:
- edit(): Renders the profile settings page and passes email verification flags and status messages to the frontend.
- update(): Validates incoming profile data, resets email verification when the email changes, saves the user record, flashes a success message, and redirects back to the profile edit page.
- destroy(): Logs out the user, deletes the user record, invalidates the session, regenerates the CSRF token, and redirects to the home page.

```mermaid
classDiagram
class ProfileController {
+edit(request) Response
+update(ProfileUpdateRequest) RedirectResponse
+destroy(ProfileDeleteRequest) RedirectResponse
}
class ProfileUpdateRequest {
+rules() array
}
class ProfileDeleteRequest {
+rules() array
}
class User {
+name string
+email string
+email_verified_at datetime
+save() bool
}
ProfileController --> ProfileUpdateRequest : "validates"
ProfileController --> ProfileDeleteRequest : "validates"
ProfileController --> User : "updates/deletes"
```

**Diagram sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [User.php:32-49](file://app/Models/User.php#L32-L49)

**Section sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)

### Validation Rules and Business Logic
ProfileUpdateRequest and ProfileDeleteRequest encapsulate validation logic:
- ProfileUpdateRequest: Uses ProfileValidationRules to enforce name and email constraints, ensuring uniqueness per user ID.
- ProfileDeleteRequest: Uses PasswordValidationRules to require the current password for account deletion.

```mermaid
flowchart TD
Start([Validation Entry]) --> LoadTraits["Load validation traits"]
LoadTraits --> NameRules["Apply name rules"]
LoadTraits --> EmailRules["Apply email rules with unique constraint"]
NameRules --> Combine["Combine rules"]
EmailRules --> Combine
Combine --> ReturnRules["Return validation rules"]
ReturnRules --> End([Validation Exit])
```

**Diagram sources**
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileValidationRules.php:16-50](file://app/Concerns/ProfileValidationRules.php#L16-L50)

**Section sources**
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [ProfileValidationRules.php:16-50](file://app/Concerns/ProfileValidationRules.php#L16-L50)
- [PasswordValidationRules.php:25-28](file://app/Concerns/PasswordValidationRules.php#L25-L28)

### Profile Settings Page Implementation
The profile settings page renders:
- A form for updating name and email with default values populated from the authenticated user.
- Conditional display of email verification status and resend verification link when applicable.
- Success messaging when a verification link is resent.
- Integration with the delete account component for secure account termination.

```mermaid
sequenceDiagram
participant Page as "Profile Settings Page"
participant Form as "Form Component"
participant Controller as "ProfileController"
participant Status as "Status Messages"
Page->>Form : Render form with defaults
Form->>Controller : Submit update
Controller-->>Page : Flash success toast
Page->>Status : Display success message
Page->>Page : Show verification status if needed
```

**Diagram sources**
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)

**Section sources**
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)

### Account Deletion Workflow
The delete account component provides:
- A confirmation dialog with password input.
- Validation of the current password before proceeding.
- Secure deletion after successful validation, including session cleanup and redirection.

```mermaid
sequenceDiagram
participant Component as "Delete User Component"
participant Form as "Delete Form"
participant Controller as "ProfileController"
participant Session as "Session"
Component->>Form : Open dialog and show password field
Form->>Controller : Submit password
Controller->>Session : Logout user
Controller->>Controller : Delete user
Controller->>Session : Invalidate and regenerate token
Controller-->>Component : Redirect to home
```

**Diagram sources**
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [ProfileController.php:49-61](file://app/Http/Controllers/Settings/ProfileController.php#L49-L61)

**Section sources**
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [ProfileController.php:49-61](file://app/Http/Controllers/Settings/ProfileController.php#L49-L61)

### Email Change Handling and Verification Status Reset
When a user updates their email address:
- The controller detects changes to the email field.
- The email verification timestamp is cleared to require re-verification.
- The frontend conditionally displays verification prompts and success messages.

```mermaid
flowchart TD
Start([Profile Update]) --> CheckEmail["Check if email is dirty"]
CheckEmail --> IsDirty{"Email changed?"}
IsDirty --> |Yes| ClearVerify["Set email_verified_at = null"]
IsDirty --> |No| SkipClear["Skip verification reset"]
ClearVerify --> SaveUser["Save user record"]
SkipClear --> SaveUser
SaveUser --> FlashToast["Flash success toast"]
FlashToast --> Redirect["Redirect to profile.edit"]
Redirect --> End([Done])
```

**Diagram sources**
- [ProfileController.php:31-44](file://app/Http/Controllers/Settings/ProfileController.php#L31-L44)

**Section sources**
- [ProfileController.php:31-44](file://app/Http/Controllers/Settings/ProfileController.php#L31-L44)

### Integration with Authentication System and Session Management
Authentication and session management during profile changes:
- Routes enforce authentication for profile operations and require email verification for destructive actions.
- Inertia middleware shares authenticated user data and application configuration.
- ProfileController performs logout, session invalidation, and CSRF token regeneration during account deletion.

```mermaid
graph TB
subgraph "Authentication & Session"
RT["Routes<br/>settings.php"]
IM["Inertia Middleware<br/>HandleInertiaRequests"]
AC["Auth Config<br/>auth.php"]
PC["ProfileController<br/>Logout & Session"]
end
RT --> PC
IM --> PC
AC --> IM
```

**Diagram sources**
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:36-46](file://app/Http/Middleware/HandleInertiaRequests.php#L36-L46)
- [auth.php:40-45](file://config/auth.php#L40-L45)
- [ProfileController.php:53-58](file://app/Http/Controllers/Settings/ProfileController.php#L53-L58)

**Section sources**
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:36-46](file://app/Http/Middleware/HandleInertiaRequests.php#L36-L46)
- [auth.php:40-45](file://config/auth.php#L40-L45)
- [ProfileController.php:53-58](file://app/Http/Controllers/Settings/ProfileController.php#L53-L58)

## Dependency Analysis
The profile management system exhibits clear separation of concerns:
- Controller depends on validation requests and the User model.
- Validation requests rely on reusable traits for consistent rules.
- Frontend components depend on the controller action signatures and route names.
- Routing enforces middleware policies for authentication and verification.
- Middleware ensures shared data availability across requests.

```mermaid
graph LR
PS["Profile Settings Page"] --> PC["ProfileController"]
DU["Delete User Component"] --> PC
PC --> PUR["ProfileUpdateRequest"]
PC --> PDR["ProfileDeleteRequest"]
PUR --> PV["ProfileValidationRules"]
PDR --> PW["PasswordValidationRules"]
PC --> U["User Model"]
RT["Routes/settings.php"] --> PC
IM["HandleInertiaRequests"] --> PS
IM --> DU
```

**Diagram sources**
- [profile.tsx:18-129](file://resources/js/pages/settings/profile.tsx#L18-L129)
- [delete-user.tsx:19-120](file://resources/js/components/delete-user.tsx#L19-L120)
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [ProfileValidationRules.php:9-51](file://app/Concerns/ProfileValidationRules.php#L9-L51)
- [PasswordValidationRules.php:8-29](file://app/Concerns/PasswordValidationRules.php#L8-L29)
- [User.php:32-49](file://app/Models/User.php#L32-L49)
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:36-46](file://app/Http/Middleware/HandleInertiaRequests.php#L36-L46)

**Section sources**
- [ProfileController.php:15-62](file://app/Http/Controllers/Settings/ProfileController.php#L15-L62)
- [ProfileUpdateRequest.php:9-22](file://app/Http/Requests/Settings/ProfileUpdateRequest.php#L9-L22)
- [ProfileDeleteRequest.php:9-24](file://app/Http/Requests/Settings/ProfileDeleteRequest.php#L9-L24)
- [ProfileValidationRules.php:9-51](file://app/Concerns/ProfileValidationRules.php#L9-L51)
- [PasswordValidationRules.php:8-29](file://app/Concerns/PasswordValidationRules.php#L8-L29)
- [User.php:32-49](file://app/Models/User.php#L32-L49)
- [settings.php:8-27](file://routes/settings.php#L8-L27)
- [HandleInertiaRequests.php:36-46](file://app/Http/Middleware/HandleInertiaRequests.php#L36-L46)

## Performance Considerations
- Minimize unnecessary database writes by checking for dirty attributes before saving.
- Use unique validation rules efficiently to avoid redundant queries.
- Keep frontend form submissions lightweight and leverage Inertia's optimistic updates where appropriate.
- Ensure session invalidation and token regeneration occur promptly during destructive operations to prevent session fixation vulnerabilities.

## Troubleshooting Guide
Common issues and resolutions:
- Profile update fails validation: Verify that name and email meet the defined constraints and that the email is unique per user ID.
- Email verification not resetting: Confirm that the email field is being modified and that the controller clears the verification timestamp accordingly.
- Account deletion requires current password: Ensure the password matches the user's current credentials before deletion.
- Session persistence after deletion: Confirm that logout, session invalidation, and token regeneration are executed in the controller.

**Section sources**
- [ProfileUpdateTest.php:15-85](file://tests/Feature/Settings/ProfileUpdateTest.php#L15-L85)
- [ProfileController.php:31-61](file://app/Http/Controllers/Settings/ProfileController.php#L31-L61)

## Conclusion
The profile management system provides a secure, user-friendly interface for managing personal information, with robust validation, clear feedback mechanisms, and strong integration with the authentication and session management layers. The modular design ensures maintainability and extensibility, while the frontend components deliver a responsive user experience.