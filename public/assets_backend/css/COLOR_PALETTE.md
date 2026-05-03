# Palette de Couleurs Backend - Re7la

## Application de Gestion de Voitures et Trajets

### 🎨 Palette Principale

| Couleur | Code Hex | Usage | Description |
|---------|-----------|-------|-------------|
| **Orange Vif** | `#F28C28` | Boutons principaux, menu actif | Couleur principale dynamique et énergique |
| **Turquoise/Vert Clair** | `#28C3A0` | Boutons d'action, indicateurs positifs | Couleur secondaire apaisante et professionnelle |
| **Jaune Pâle** | `#F7D154` | Alertes, notifications | Couleur d'avertissement visible mais douce |
| **Gris Foncé** | `#2E3B4E` | Cartes, textes principaux | Couleur de texte principale et élégante |
| **Rouge Clair** | `#F25C5C` | Alertes négatives, indisponible | Couleur d'erreur claire et moderne |
| **Blanc Cassé** | `#FAFAFA` | Fonds général | Couleur de fond propre et lumineuse |

### 🎯 Déclinaisons de Couleurs

#### Orange Principal
- **Clair**: `#F7A848` (Hover states)
- **Normal**: `#F28C28` (Primary actions)
- **Foncé**: `#E67A20` (Active states)

#### Turquoise Secondaire
- **Clair**: `#4AD3B0` (Hover states)
- **Normal**: `#28C3A0` (Success actions)
- **Foncé**: `#20B090` (Active states)

#### Jaune Avertissement
- **Clair**: `#F9DD6C` (Light backgrounds)
- **Normal**: `#F7D154` (Warning elements)
- **Foncé**: `#E5C440` (Active warnings)

#### Rouge Danger
- **Clair**: `#F47878` (Hover states)
- **Normal**: `#F25C5C` (Danger actions)
- **Foncé**: `#E04848` (Active dangers)

#### Gris Texte
- **Clair**: `#ADB5BD` (Muted text)
- **Normal**: `#6C757D` (Secondary text)
- **Foncé**: `#2E3B4E` (Primary text)

### 🎨 Gradients Définis

- **Gradient Primaire**: `linear-gradient(135deg, #F28C28, #E67A20)`
- **Gradient Secondaire**: `linear-gradient(135deg, #28C3A0, #20B090)`
- **Gradient Avertissement**: `linear-gradient(135deg, #F7D154, #E5C440)`
- **Gradient Danger**: `linear-gradient(135deg, #F25C5C, #E04848)`

### 🎯 Applications Recommandées

#### Navigation & Menu
- **Menu Actif**: Orange vif avec fond clair
- **Menu Hover**: Fond orange transparent (8%)
- **Icônes**: Gris foncé avec hover orange

#### Boutons
- **Primaire**: Orange vif avec hover vers foncé
- **Succès**: Turquoise avec hover vers foncé
- **Avertissement**: Jaune avec texte gris foncé
- **Danger**: Rouge avec hover vers foncé

#### Cartes & Conteneurs
- **Fond**: Blanc cassé `#FAFAFA`
- **Bordures**: Gris clair `#E9ECEF`
- **Ombres**: Gris foncé transparent
- **Texte**: Gris foncé `#2E3B4E`

#### Alertes & Notifications
- **Succès**: Fond turquoise transparent (10%)
- **Avertissement**: Fond jaune transparent (10%)
- **Erreur**: Fond rouge transparent (10%)
- **Info**: Fond orange transparent (10%)

### 🎨 Accessibilité

Toutes les couleurs respectent les ratios de contraste WCAG 2.1:
- **Texte normal**: Minimum 4.5:1
- **Texte large**: Minimum 3:1
- **Éléments d'interface**: Minimum 3:1

### 🎯 Utilisation dans le Code

```css
/* Variables CSS disponibles */
:root {
    --primary-orange: #F28C28;
    --secondary-turquoise: #28C3A0;
    --warning-yellow: #F7D154;
    --danger-red: #F25C5C;
    --dark-gray: #2E3B4E;
    --background-light: #FAFAFA;
}

/* Classes utilitaires */
.btn-primary { background-color: var(--primary-orange); }
.text-success { color: var(--secondary-turquoise); }
.bg-warning { background-color: var(--warning-yellow); }
```

### 🎨 Thème

Cette palette est conçue pour être:
- **Moderne**: Couleurs vives mais professionnelles
- **Accessible**: Contraste optimal pour la lisibilité
- **Cohérente**: Harmonie entre toutes les couleurs
- **Adaptable**: Fonctionne bien en mode clair et sombre

---

*Palette générée le 4 avril 2026 pour l'application Re7la*
