# SarvCast UI Design System

## Overview
SarvCast is a Persian audio story platform for children with a vibrant, playful design system that emphasizes accessibility, readability, and child-friendly aesthetics.

## Color Palette

### Primary Colors
- **Primary Blue**: `#00BCD4` - Bright cyan blue (main brand color)
- **Secondary Orange**: `#FF5722` - Vibrant orange (accent color)

### Accent Colors
- **Accent Pink**: `#E91E63` - Hot pink
- **Accent Green**: `#4CAF50` - Bright green
- **Accent Purple**: `#9C27B0` - Vibrant purple
- **Accent Yellow**: `#FFEB3B` - Bright yellow
- **Accent Orange**: `#FF9800` - Warm orange
- **Accent Teal**: `#00BCD4` - Bright teal
- **Accent Lime**: `#CDDC39` - Lime green
- **Accent Indigo**: `#3F51B5` - Deep indigo
- **Accent Red**: `#F44336` - Bright red
- **Accent Blue**: `#2196F3` - Bright blue
- **Accent Cyan**: `#00BCD4` - Cyan
- **Accent Magenta**: `#E91E63` - Magenta
- **Accent Gold**: `#B8860B` - Dark goldenrod (more visible on light backgrounds)
- **Accent Silver**: `#C0C0C0` - Silver

### Background Colors
- **Background Light**: `#E8F4FD` - Bright sky blue background
- **Background White**: `#FFFFFF` - Pure white
- **Background Gradient 1**: `#E1F5FE` - Bright cyan gradient
- **Background Gradient 2**: `#FCE4EC` - Bright pink gradient
- **Background Gradient 3**: `#E8F5E8` - Soft green gradient
- **Background Gradient 4**: `#FEF7E0` - Soft yellow gradient

### Text Colors
- **Text Primary**: `#1A237E` - Deep blue for primary text
- **Text Secondary**: `#424242` - Dark gray for secondary text
- **Text Light**: `#757575` - Medium gray for subtle text
- **Text White**: `#FFFFFF` - White text

### Surface Colors
- **Card Background**: `#FFFFFF` - White cards
- **Card Shadow**: `#1A000000` - Subtle shadow (10% opacity)
- **Surface Light**: `#FAFAFA` - Light surface

### Status Colors
- **Success**: `#4CAF50` - Green for success states
- **Warning**: `#FF9800` - Orange for warnings
- **Error**: `#F44336` - Red for errors
- **Info**: `#2196F3` - Blue for info messages

## Gradients

### Primary Gradients
- **Primary Gradient**: Primary Blue → Accent Teal (top-left to bottom-right)
- **Secondary Gradient**: Secondary Orange → Accent Yellow (top-left to bottom-right)
- **Background Gradient**: Background Gradient 1 → Background Gradient 2 (top-center to bottom-center)

### Special Gradients
- **Rainbow Gradient**: Red → Orange → Yellow → Green → Cyan → Primary Blue → Purple → Magenta
- **Candy Gradient**: Pink → Yellow → Cyan → Lime → Gold
- **Sunset Gradient**: Orange → Pink → Purple → Indigo

## Typography

### Font Families
- **Primary Font**: `Pofak` - Custom Persian font family
  - Regular (400)
  - Bold (700)
  - Black (900)
  - Thin (100)
- **Secondary Font**: `IranSans` - Persian system font
  - Regular (400)
  - Medium (500)
  - Bold (700)
  - Light (300)
  - Thin (100)
- **Fallback Font**: `Roboto` - System font

### Text Styles

#### Display Styles
- **Display Large**: 32px, Bold, Primary Blue
- **Display Medium**: 28px, Bold, Primary Blue
- **Display Small**: 24px, SemiBold (600), Primary Blue

#### Headline Styles
- **Headline Large**: 22px, Bold, Primary Blue
- **Headline Medium**: 20px, SemiBold (600), Primary Blue
- **Headline Small**: 18px, SemiBold (600), Primary Blue

#### Title Styles
- **Title Large**: 16px, SemiBold (600), Primary Blue
- **Title Medium**: 14px, Medium (500), Primary Blue
- **Title Small**: 12px, Medium (500), Secondary Gray

#### Body Styles
- **Body Large**: 16px, Regular, Primary Blue
- **Body Medium**: 14px, Regular, Primary Blue
- **Body Small**: 12px, Regular, Secondary Gray

#### Label Styles
- **Label Large**: 14px, Medium (500), Primary Blue
- **Label Medium**: 12px, Medium (500), Secondary Gray
- **Label Small**: 10px, Medium (500), Light Gray

## Spacing System

### Spacing Scale
- **XS**: 4px
- **S**: 8px
- **M**: 16px
- **L**: 24px
- **XL**: 32px
- **XXL**: 48px

## Border Radius System

### Radius Scale (Extra Rounded for Kids)
- **S**: 12px
- **M**: 20px
- **L**: 28px
- **XL**: 36px
- **XXL**: 48px

## Animation Durations

### Animation Timing
- **Fast**: 200ms
- **Normal**: 300ms
- **Slow**: 500ms

## Component Styles

### Button Styles

#### Primary Button
- Background: Primary Blue
- Text: White
- Border Radius: 16px
- Padding: 24px horizontal, 12px vertical
- Elevation: 4px
- Shadow: Primary Blue with 30% opacity

#### Secondary Button
- Background: Transparent
- Text: Primary Blue
- Border: 2px Primary Blue
- Border Radius: 16px
- Padding: 24px horizontal, 12px vertical
- Elevation: 0px

#### Accent Button
- Background: Secondary Orange
- Text: White
- Border Radius: 16px
- Padding: 24px horizontal, 12px vertical
- Elevation: 4px
- Shadow: Secondary Orange with 30% opacity

### Card Styles

#### Standard Card
- Background: White
- Border Radius: 20px
- Shadow: Subtle black with 10% opacity
- Blur: 10px
- Offset: (0, 4px)

#### Primary Card
- Background: Primary Gradient
- Border Radius: 20px
- Shadow: Primary Blue with 30% opacity
- Blur: 15px
- Offset: (0, 6px)

### Input Styles
- Background: White
- Border Radius: 16px
- Border: None (default)
- Focus Border: 2px Primary Blue
- Error Border: 2px Error Red
- Padding: 16px horizontal, 12px vertical
- Hint Text: Light Gray

## Icon System

### Icon Library
The app uses a comprehensive SVG icon library with 919+ icons including:

#### Navigation Icons
- `home.svg` - Home page
- `search-normal.svg` - Search functionality
- `book.svg` - Library
- `heart.svg` - Favorites
- `profile.svg` - User profile

#### Story Related Icons
- `play.svg` - Play audio
- `pause.svg` - Pause audio
- `stop.svg` - Stop audio
- `volume-up.svg` - Volume control
- `volume-mute.svg` - Mute audio
- `star.svg` - Rating/favorites
- `clock.svg` - Duration/time
- `category.svg` - Story categories

#### UI Icons
- `arrow-left.svg` - Back navigation
- `arrow-right.svg` - Forward navigation
- `close-circle.svg` - Close/dismiss
- `tick-circle.svg` - Success/check
- `add-circle.svg` - Add action
- `minus-circle.svg` - Remove action
- `edit.svg` - Edit action
- `share.svg` - Share content
- `download.svg` - Download content
- `upload.svg` - Upload content

#### Kids Friendly Icons
- `emoji-happy.svg` - Happy face
- `emoji-sad.svg` - Sad face
- `magic-star.svg` - Magic/fantasy
- `gift.svg` - Gift/reward
- `cake.svg` - Celebration
- `color-swatch.svg` - Rainbow/colors
- `bubble.svg` - Fun/playful
- `crown.svg` - Premium/special
- `medal.svg` - Achievement

### Icon Usage
- **Size**: Typically 24px for navigation, 18px for small actions
- **Color**: Inherits from parent or uses theme colors
- **Format**: SVG for scalability and customization

## Layout Patterns

### Navigation Bar
- **Background**: White with rounded top corners (24px radius)
- **Shadow**: Upward shadows for floating effect
- **Selected State**: Light blue background with subtle border
- **Animation**: 200ms smooth transitions

### Story Cards
- **Featured Story Card**: Horizontal layout with rainbow border
- **Recent Story Card**: Vertical layout with image on top
- **Standard Story Card**: Compact horizontal layout
- **Border**: Rainbow gradient border (3px margin)
- **Shadow**: Pink and purple shadows for depth

### Search Input
- **Background**: Rainbow gradient border with white inner background
- **Border Radius**: Large radius (XL)
- **Shadow**: Pink and purple shadows
- **Padding**: Medium spacing

### Empty States
- **Icon**: Circular gradient background
- **Typography**: Pofak font family
- **Layout**: Centered with proper spacing
- **Action Buttons**: Primary button style

## RTL Support

### Text Direction
- **Primary Language**: Persian (Farsi)
- **Text Direction**: Right-to-Left (RTL)
- **Text Alignment**: Right-aligned for Persian text
- **Layout Direction**: RTL for proper Persian UI flow

### Font Support
- **Persian Fonts**: Pofak and IranSans for proper Persian character rendering
- **Fallback**: Roboto for system compatibility
- **Weight Support**: Multiple weights for typography hierarchy

## Accessibility

### Color Contrast
- **Primary Text**: High contrast against white backgrounds
- **Secondary Text**: Sufficient contrast for readability
- **Interactive Elements**: Clear visual feedback

### Touch Targets
- **Minimum Size**: 44px for touch targets
- **Spacing**: Adequate spacing between interactive elements
- **Visual Feedback**: Clear selected/unselected states

## Responsive Design

### Breakpoints
- **Mobile**: Primary target (320px - 768px)
- **Tablet**: Secondary support (768px - 1024px)
- **Desktop**: Limited support (1024px+)

### Adaptive Elements
- **Spacing**: Scales with screen size
- **Typography**: Maintains readability across devices
- **Touch Targets**: Appropriate sizing for each platform

## Design Principles

### Child-Friendly Design
- **Bright Colors**: Vibrant, engaging color palette
- **Rounded Corners**: Soft, friendly shapes
- **Playful Gradients**: Rainbow and candy gradients for fun
- **Clear Hierarchy**: Easy to understand information structure

### Persian Language Support
- **RTL Layout**: Proper right-to-left text flow
- **Persian Fonts**: Custom fonts for authentic Persian typography
- **Cultural Considerations**: Appropriate color and design choices

### Accessibility First
- **High Contrast**: Clear text and background relationships
- **Touch Friendly**: Large, easy-to-tap interactive elements
- **Clear Feedback**: Obvious selected and unselected states
- **Consistent Patterns**: Predictable interaction patterns

## Usage Guidelines

### Color Usage
- **Primary Blue**: Use for main actions, selected states, and brand elements
- **Accent Colors**: Use sparingly for highlights and special elements
- **Rainbow Gradients**: Use for special cards, borders, and premium features
- **Status Colors**: Use consistently for success, warning, error, and info states

### Typography Usage
- **Pofak Font**: Use for headings and important text
- **IranSans Font**: Use for body text and secondary information
- **Size Hierarchy**: Follow the defined text style hierarchy
- **Weight Hierarchy**: Use appropriate font weights for emphasis

### Component Usage
- **Consistent Spacing**: Use the defined spacing scale
- **Proper Radius**: Use appropriate border radius for different element types
- **Shadow Consistency**: Use consistent shadow patterns for depth
- **Animation Timing**: Use appropriate animation durations for smooth interactions

This design system ensures consistency, accessibility, and child-friendly design throughout the SarvCast application while maintaining proper Persian language support and RTL layout patterns.
