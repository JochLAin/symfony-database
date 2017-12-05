<?php 

namespace JochLAin\Database\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class Contains extends FunctionNode
{
    protected $left;
    protected $right;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->left = $parser->SingleValuedPathExpression();
        $parser->match(Lexer::T_COMMA);
        $this->right = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker)
    {
        return sprintf(
            "(jsonb_exists(%s::jsonb, '%s'))",
            $this->left->dispatch($walker),
            $this->right->value
        );
    }
}