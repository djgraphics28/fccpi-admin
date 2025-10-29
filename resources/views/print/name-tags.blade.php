<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Youth Fellowship Name Tags</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Arial Black", Arial, sans-serif;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            justify-items: center;
            align-items: center;
            background: #fff;
            gap: 0.5px;
        }

        .name-tag {
            width: 100%;
            height: 260px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border: 8px solid #000;
            box-sizing: border-box;
            padding: 10px;
            border-radius: 12px;
            margin: 0.5px;
            background-image: linear-gradient(rgba(255, 255, 255, 0.7), rgba(255, 255, 255, 0.7)),
                url('/images/nametag-bg.png');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .name-tag h1 {
            font-size: 85px;
            /* increased from 75px */
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            line-height: 1;
            -webkit-text-stroke: 4px #000;
            /* black border around text */
            text-shadow:
                -1px -1px 0 #fff,
                1px -1px 0 #fff,
                -1px 1px 0 #fff,
                1px 1px 0 #fff;
        }


        /* White text border (stroke effect) */
        .event-title,
        .name-tag h1,
        .color-group {
            text-shadow:
                -1px -1px 0 #fff,
                1px -1px 0 #fff,
                -1px 1px 0 #fff,
                1px 1px 0 #fff;
        }

        .event-title {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #444;
        }

        .name-tag h1 {
            font-size: 75px;
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            line-height: 1;
        }

        .color-group {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                gap: 0.5px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .name-tag {
                page-break-inside: avoid;
                margin: 0.5px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    @php
        $colorNames = [
            '#3B82F6' => 'Blue Group',
            '#EF4444' => 'Red Group',
            '#10B981' => 'Green Group',
            '#F59E0B' => 'Yellow Group',
            '#8B5CF6' => 'Purple Group',
            '#EC4899' => 'Pink Group',
            '#06B6D4' => 'Cyan Group',
            '#84CC16' => 'Lime Group',
            '#F97316' => 'Orange Group',
            '#6366F1' => 'Indigo Group',
            '#14B8A6' => 'Teal Group',
            '#EAB308' => 'Amber Group',
            '#A855F7' => 'Violet Group',
            '#D946EF' => 'Fuchsia Group',
            '#0EA5E9' => 'Sky Group',
            '#22C55E' => 'Emerald Group',
            '#FACC15' => 'Gold Group',
            '#FB923C' => 'Coral Group',
            '#C084FC' => 'Lavender Group',
            '#F472B6' => 'Rose Group',
        ];
    @endphp

    @foreach ($youths as $youth)
        @php
            $colorValue = strtoupper(trim($youth->color));
            $colorName = $colorNames[$colorValue] ?? 'FACILITATOR Group';
        @endphp

        <div class="name-tag" style="border-color: {{ $colorValue }}">
            <div class="event-title">
                FCCPI YOUTH JOINT FELLOWSHIP 2025
            </div>
            <h1 style="color: {{ $colorValue }}">{{ strtoupper($youth->first_name) }}</h1>
            <div class="color-group" style="color: {{ $colorValue }}">
                {{ $colorName }}
            </div>
        </div>
    @endforeach

    <script>
        window.onload = () => window.print();
    </script>
</body>

</html>
