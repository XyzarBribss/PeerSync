<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Zeki</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Zeki Chatbot</h1>
        <form class="" action="" method="post">
            <div class="mb-4">
                <input type="text" required name="question" placeholder="Write your question..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" name="ask" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition duration-300">Send</button>
        </form>
        <?php
        // Variables for time question
        date_default_timezone_set('Europe/Istanbul');
        $time = "The current time is " . date("H:i");
        // Variables for time question

        if (isset($_POST["ask"])) {
            // API key
            $api_key = 'AIzaSyCWYdN2MaJX8HUyf9gKdZzBEt8z7qWLtWY';
            // API key

            // Endpoint
            $endpoint_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $api_key;
            // Endpoint

            // Defined questions array
            $qa_pairs = array(
                'What is your name' => 'My name is Zeki',
                'Who are you' => 'I am your assistant robot',
                'How old are you' => 'I am a robot, I do not have an age',
                'Hello' => 'Hello, I am Zeki. How can I help you?',
                'Hi' => 'Hi, I am Zeki. What can I do for you?',
                'Are you going to take over the world' => 'I do not have such a plan for now',
                'Hello World' => 'Are you a secret hacker?',
                'Chatgpt' => 'I know, a good friend :D',
                'I am fine' => 'I am glad you are fine. How can I help you?',
                'How are you' => 'I am very good. How are you?',
                'How are you Chatgpt' => 'Chatgpt is a good friend, I am Zeki',
                'What time is it' => $time
            );
            // Defined questions array

            // Responses for questions containing chatgpt
            $gpty_response = array(
                '1' => 'I know, a good friend indeed',
                '2' => 'Let me help you, I am robot Zeki'
            );

            // User message
            $user_message = $_POST["question"];

            // Clean special characters
            $user_message = preg_replace("/[^a-zA-ZıİiIğĞüÜşŞöÖçÇ0-9]+/u", " ", $user_message);
            $user_message = trim($user_message);
            // Clean special characters

            // If the message contains chatgpt, give a random response from the array
            if (stripos($user_message, 'Chatgpt') !== false) {
                $rand = array_rand($gpty_response);
                $response = $gpty_response[$rand];
            } else {
                // Otherwise, check the user's question in the defined questions
                $response = null;
                foreach ($qa_pairs as $question => $answer) {
                    // Clean special characters in the question
                    $clean_question = preg_replace("/[^a-zA-ZıİiIğĞüÜşŞöÖçÇ0-9]+/u", " ", $question);
                    $clean_question = trim($clean_question);

                    similar_text($user_message, $clean_question, $similarity);

                    if ($similarity >= 60) {
                        $response = $answer;
                        break;
                    }
                }

                if ($response === null) {
                    // If no match in defined questions, use Gemini AI API
                    $data = array(
                        "contents" => array(
                            array(
                                "role" => "user",
                                "parts" => array(
                                    array(
                                        "text" => $user_message
                                    )
                                )
                            )
                        ),
                        "generationConfig" => array(
                            "temperature" => 1,
                            "topK" => 64,
                            "topP" => 0.95,
                            "maxOutputTokens" => 8192,
                            "responseMimeType" => "text/plain"
                        ),
                        "safetySettings" => array(
                            array(
                                "category" => "HARM_CATEGORY_HARASSMENT",
                                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                            ),
                            array(
                                "category" => "HARM_CATEGORY_HATE_SPEECH",
                                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                            ),
                            array(
                                "category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                            ),
                            array(
                                "category" => "HARM_CATEGORY_DANGEROUS_CONTENT",
                                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
                            )
                        )
                    );

                    $json_data = json_encode($data);

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, $endpoint_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/json'
                    ));

                    $response = curl_exec($ch);

                    if ($response === false) {
                        $response = "Sorry, I do not have information on this topic. Please try again.";
                    } else {
                        $json_response = json_decode($response, true);

                        if (!isset($json_response['candidates'][0]['content']['parts'])) {
                            $response = "Sorry, I do not have information on this topic. Please try again.";
                        } else {
                            $response = '';
                            foreach ($json_response['candidates'][0]['content']['parts'] as $part) {
                                $response .= $part['text'] . ' ';
                            }
                        }
                    }

                    curl_close($ch);
                }
            }

            echo "<hr class='my-4'>";
            echo "<div class='bg-gray-100 p-4 rounded-lg'>";
            echo $response;
            echo "</div>";
        }
        ?>
    </div>
</body>

</html>
