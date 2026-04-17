<?php

namespace Database\Seeders;

use App\Models\Choice;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleQuizSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'teacher@classquiz.local')->first();

        if (!$teacher) {
            return;
        }

        foreach ($this->quizzes() as $quizData) {
            $quiz = Quiz::updateOrCreate(
                [
                    'creator_id' => $teacher->id,
                    'title' => $quizData['title'],
                ],
                [
                    'description' => $quizData['description'],
                ]
            );

            foreach ($quizData['questions'] as $index => $questionData) {
                $question = Question::updateOrCreate(
                    [
                        'quiz_id' => $quiz->id,
                        'tag' => $questionData['tag'],
                    ],
                    [
                        'type' => $questionData['type'],
                        'text' => $questionData['text'],
                        'points' => $questionData['points'],
                        'sort_order' => $index + 1,
                        'is_enabled' => true,
                        'settings' => $questionData['settings'] ?? [],
                        'explanation' => $questionData['explanation'] ?? null,
                    ]
                );

                foreach ($questionData['choices'] as $choiceIndex => $choiceData) {
                    Choice::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'sort_order' => $choiceIndex + 1,
                        ],
                        [
                            'text' => $choiceData['text'],
                            'is_correct' => $choiceData['is_correct'],
                        ]
                    );
                }
            }
        }
    }

    private function quizzes(): array
    {
        return [
            [
                'title' => 'CSTE 3109 (Artificial Intelligence) - CT2',
                'description' => 'Sample AI class test covering search, logic, and knowledge representation.',
                'questions' => [
                    [
                        'tag' => 'CT2-Q1',
                        'type' => 'mcq_single',
                        'text' => 'Which search strategy is guaranteed to find the shallowest goal first when all step costs are equal?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'Depth-first search', 'is_correct' => false],
                            ['text' => 'Breadth-first search', 'is_correct' => true],
                            ['text' => 'Hill climbing', 'is_correct' => false],
                            ['text' => 'Beam search', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT2-Q2',
                        'type' => 'tf',
                        'text' => 'A heuristic used by A* search must never overestimate the true remaining cost if it is to remain admissible.',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'True', 'is_correct' => true],
                            ['text' => 'False', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT2-Q3',
                        'type' => 'mcq_single',
                        'text' => 'In propositional logic, which connective is true only when both operands have the same truth value?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'Implication', 'is_correct' => false],
                            ['text' => 'Exclusive OR', 'is_correct' => false],
                            ['text' => 'Biconditional', 'is_correct' => true],
                            ['text' => 'Disjunction', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT2-Q4',
                        'type' => 'mcq_multi',
                        'text' => 'Which of the following are common components of an intelligent agent?',
                        'points' => 2,
                        'settings' => ['mcq_multi_grading' => 'all_or_nothing'],
                        'choices' => [
                            ['text' => 'Sensors', 'is_correct' => true],
                            ['text' => 'Actuators', 'is_correct' => true],
                            ['text' => 'Compiler directives', 'is_correct' => false],
                            ['text' => 'Performance measure', 'is_correct' => true],
                        ],
                    ],
                    [
                        'tag' => 'CT2-Q5',
                        'type' => 'mcq_single',
                        'text' => 'Which knowledge representation technique is especially useful for expressing hierarchical relationships such as "is-a"?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'Semantic network', 'is_correct' => true],
                            ['text' => 'Minimax tree', 'is_correct' => false],
                            ['text' => 'Confusion matrix', 'is_correct' => false],
                            ['text' => 'Kalman filter', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'CSTE 3109 (Artificial Intelligence) - CT3',
                'description' => 'Sample AI class test focused on machine learning, uncertainty, and planning.',
                'questions' => [
                    [
                        'tag' => 'CT3-Q1',
                        'type' => 'mcq_single',
                        'text' => 'Which learning paradigm relies on labeled training examples?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'Unsupervised learning', 'is_correct' => false],
                            ['text' => 'Reinforcement learning', 'is_correct' => false],
                            ['text' => 'Supervised learning', 'is_correct' => true],
                            ['text' => 'Evolutionary search', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT3-Q2',
                        'type' => 'mcq_single',
                        'text' => 'In a decision tree, which metric is commonly used to choose the best attribute split in ID3?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'Entropy / information gain', 'is_correct' => true],
                            ['text' => 'Euclidean distance', 'is_correct' => false],
                            ['text' => 'Page rank', 'is_correct' => false],
                            ['text' => 'Mean absolute error only', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT3-Q3',
                        'type' => 'tf',
                        'text' => 'Naive Bayes assumes conditional independence of features given the class.',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'True', 'is_correct' => true],
                            ['text' => 'False', 'is_correct' => false],
                        ],
                    ],
                    [
                        'tag' => 'CT3-Q4',
                        'type' => 'mcq_multi',
                        'text' => 'Which of the following are valid examples of uncertainty handling in AI?',
                        'points' => 2,
                        'settings' => ['mcq_multi_grading' => 'all_or_nothing'],
                        'choices' => [
                            ['text' => 'Bayesian networks', 'is_correct' => true],
                            ['text' => 'Fuzzy logic', 'is_correct' => true],
                            ['text' => 'Alpha-beta pruning', 'is_correct' => false],
                            ['text' => 'Hidden Markov models', 'is_correct' => true],
                        ],
                    ],
                    [
                        'tag' => 'CT3-Q5',
                        'type' => 'mcq_single',
                        'text' => 'What is the main goal of a heuristic in problem solving?',
                        'points' => 1,
                        'choices' => [
                            ['text' => 'To guarantee optimality regardless of algorithm', 'is_correct' => false],
                            ['text' => 'To reduce search effort by guiding exploration', 'is_correct' => true],
                            ['text' => 'To replace the state space entirely', 'is_correct' => false],
                            ['text' => 'To remove the need for evaluation', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ];
    }
}
