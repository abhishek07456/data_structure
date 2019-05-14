#include <iostream>
using namespace std;
 union student{
   int a;
   char b[4];
      
};
int main(){
    student o;
     o.a=97;
    cout<<o.a<<" "<<o.b[2]<<endl;
}
