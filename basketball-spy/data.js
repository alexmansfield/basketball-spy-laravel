// Basketball Spy Data
// This file contains JSON data as a JavaScript object

const appData = {
    "teams": [
        {
            "name": "Golden State Warriors",
            "abbreviation": "GSW",
            "location": "Golden State",
            "nickname": "Warriors",
            "url": "https://www.nba.com/warriors/",
            "logoUrl": "https://cdn.nba.com/logos/nba/1610612744/primary/L/logo.svg",
            "color": "#1d428a",
            "players": [
                { "name": "Gary Payton II", "jersey": "0", "position": "G", "height": "6'2\"", "weight": "195 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1627780.png" },
                { "name": "Jonathan Kuminga", "jersey": "1", "position": "F", "height": "6'7\"", "weight": "225 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630228.png" },
                { "name": "Brandin Podziemski", "jersey": "2", "position": "G", "height": "6'4\"", "weight": "205 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1641764.png" },
                { "name": "Will Richard", "jersey": "3", "position": "G", "height": "6'3\"", "weight": "206 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642954.png" },
                { "name": "Moses Moody", "jersey": "4", "position": "G", "height": "6'5\"", "weight": "211 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630541.png" },
                { "name": "Buddy Hield", "jersey": "7", "position": "G", "height": "6'4\"", "weight": "220 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1627741.png" },
                { "name": "De'Anthony Melton", "jersey": "8", "position": "G", "height": "6'2\"", "weight": "200 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1629001.png" },
                { "name": "Jimmy Butler III", "jersey": "10", "position": "F", "height": "6'6\"", "weight": "230 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/202710.png" },
                { "name": "Gui Santos", "jersey": "15", "position": "F", "height": "6'7\"", "weight": "185 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630611.png" },
                { "name": "Al Horford", "jersey": "20", "position": "CF", "height": "6'8\"", "weight": "240 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/201143.png" },
                { "name": "Quinten Post", "jersey": "21", "position": "C", "height": "7'0\"", "weight": "238 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642366.png" },
                { "name": "Alex Toohey", "jersey": "22", "position": "F", "height": "6'8\"", "weight": "223 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642893.png" },
                { "name": "Draymond Green", "jersey": "23", "position": "F", "height": "6'6\"", "weight": "230 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/203110.png" },
                { "name": "Stephen Curry", "jersey": "30", "position": "G", "height": "6'2\"", "weight": "185 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/201939.png" },
                { "name": "Trayce Jackson-Davis", "jersey": "32", "position": "F", "height": "6'9\"", "weight": "245 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1631218.png" },
                { "name": "Jackson Rowe", "jersey": "44", "position": "F", "height": "6'6\"", "weight": "210 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642050.png" },
                { "name": "Pat Spencer", "jersey": "61", "position": "G", "height": "6'2\"", "weight": "205 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630311.png" }
            ]
        },
        {
            "name": "San Antonio Spurs",
            "abbreviation": "SAS",
            "location": "San Antonio",
            "nickname": "Spurs",
            "url": "https://www.nba.com/spurs/",
            "logoUrl": "https://cdn.nba.com/logos/nba/1610612759/primary/L/logo.svg",
            "color": "#000000",
            "players": [
                { "name": "Jordan McLaughlin", "jersey": "0", "position": "G", "height": "5'11\"", "weight": "185 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1629162.png" },
                { "name": "Victor Wembanyama", "jersey": "1", "position": "FC", "height": "7'4\"", "weight": "235 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1641705.png" },
                { "name": "Dylan Harper", "jersey": "2", "position": "G", "height": "6'5\"", "weight": "215 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642844.png" },
                { "name": "Keldon Johnson", "jersey": "3", "position": "FG", "height": "6'6\"", "weight": "220 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1629640.png" },
                { "name": "De'Aaron Fox", "jersey": "4", "position": "G", "height": "6'3\"", "weight": "185 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1628368.png" },
                { "name": "Stephon Castle", "jersey": "5", "position": "G", "height": "6'6\"", "weight": "215 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642264.png" },
                { "name": "Luke Kornet", "jersey": "7", "position": "CF", "height": "7'1\"", "weight": "250 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1628436.png" },
                { "name": "Kelly Olynyk", "jersey": "8", "position": "FC", "height": "7'0\"", "weight": "240 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/203482.png" },
                { "name": "Jeremy Sochan", "jersey": "10", "position": "F", "height": "6'8\"", "weight": "230 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1631110.png" },
                { "name": "Carter Bryant", "jersey": "11", "position": "F", "height": "6'6\"", "weight": "220 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642868.png" },
                { "name": "Bismack Biyombo", "jersey": "18", "position": "C", "height": "6'8\"", "weight": "255 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/202687.png" },
                { "name": "Devin Vassell", "jersey": "24", "position": "GF", "height": "6'5\"", "weight": "200 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630170.png" },
                { "name": "David Jones Garcia", "jersey": "25", "position": "F", "height": "6'4\"", "weight": "210 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642357.png" },
                { "name": "Riley Minix", "jersey": "27", "position": "F", "height": "6'7\"", "weight": "230 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1642434.png" },
                { "name": "Julian Champagnie", "jersey": "30", "position": "F", "height": "6'7\"", "weight": "217 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630577.png" },
                { "name": "Harrison Barnes", "jersey": "40", "position": "F", "height": "6'7\"", "weight": "225 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/203084.png" },
                { "name": "Lindy Waters III", "jersey": "43", "position": "F", "height": "6'5\"", "weight": "210 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1630322.png" },
                { "name": "Harrison Ingram", "jersey": "55", "position": "F", "height": "6'5\"", "weight": "230 lbs", "headshotUrl": "https://cdn.nba.com/headshots/nba/latest/260x190/1631127.png" }
            ]
        }
    ]
};

