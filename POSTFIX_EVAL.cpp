#include <iostream>
#include <stack>
#include <string>
using namespace std;
int prec(char c)
{
	if(c == '^')
	return 3;
	else if(c == '*' || c == '/')
	return 2;
	else if(c == '+' || c == '-')
	return 1;
	else
	return -1;
}
string infix_to_postfix(string s)
{
  stack <char> sk;
   sk.push('N');
   char c;
   int l=s.length();
   string out;
   for(int i=0;i<l;i++)
   {
      if((s[i] >= 'a' && s[i] <= 'z')||(s[i] >= 'A' && s[i] <= 'Z')){
      cout<<"!oops enter only numeric string"<<endl;
      return "0";
      }
      if(s[i]>='0' && s[i]<='9')
      out+=s[i];
      else if(s[i]=='(')
      sk.push('(');
      else if(s[i]==')')
      {
        while(sk.top()!='(' && sk.top()!='N')
        {
         c=sk.top();
        out+=c;
        sk.pop();
        }
        if(sk.top()=='(')
        sk.pop();

      }
      else
      {
         while(sk.top() != 'N' && prec(s[i]) <= prec(sk.top()))
			{
				c = sk.top();
				sk.pop();
				out += c;
			}
			sk.push(s[i]);

      }

   }
   while(sk.top()!='N')
   {
         c = sk.top();
				sk.pop();
				out += c;
   }
   return out;
}
void posteval(string st)
{
    int sum=0;
    stack <int> s;
    int a,b;
    for(int i=0;st[i]!='\0';i++)
    {
        if(st[i]=='+' || st[i]=='-' || st[i]=='/' || st[i]=='*')
        {
            switch(st[i])
            {
                case '+':
                a=s.top();
                s.pop();
                b=s.top();
                s.pop();
                a=b+a;
                s.push(a);

                break;
                case '-':
                a=s.top();
                s.pop();
                b=s.top();
                s.pop();
                a=b-a;

                s.push(a);

                break;
                 case '*':
                a=s.top();
                s.pop();
                b=s.top();
                s.pop();
                a=a*b;
            s.push(a);

                break;
                case '/':
                 a=s.top();
                s.pop();
                b=s.top();
                s.pop();
                a=b/a;
                s.push(a);

                break;
            }
        }
        else
        s.push(st[i]-'0');
    }
  cout<<s.top();

}
int main()
{
string s;
cout<<"Enter arthemetic Expression for postfix evalution :"<<endl;
cin>>s;
cout<<"Infix expression:"<<endl;
cout<<s<<endl;
s=infix_to_postfix(s);
cout<<"postfix expression :"<<endl;
cout<<s<<endl;
cout<<"posfix evalution of expression :"<<endl;
posteval(s);
}
